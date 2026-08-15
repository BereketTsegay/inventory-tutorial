<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DynamicFormService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DynamicFormController extends Controller
{
    protected $formService;

    public function __construct(DynamicFormService $formService)
    {
        $this->formService = $formService;
    }

    public function index(Request $request, $model)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);

        $query = $modelClass::query();

        // Structural Row Guard: Check if the model uses user relationship tracking properties
        if (method_exists($modelClass, 'user') || \Schema::hasColumn((new $modelClass)->getTable(), 'user_id')) {
            $query->where('user_id', Auth::id());
        }

        // Global Text Search Framework Engine
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($subQuery) use ($fields, $searchTerm) {
                foreach ($fields as $field) {
                    if (in_array($field['type'], ['text', 'textarea', 'number', 'email', 'tel'])) {
                        $subQuery->orWhere($field['name'], 'LIKE', $searchTerm);
                    }
                }
            });
        }

        // Apply drop-down sorting parameters
        foreach ($fields as $field) {
            if ($field['type'] === 'select' && $request->filled('filter_' . $field['name'])) {
                $query->where($field['name'], $request->input('filter_' . $field['name']));
            }
        }

        $records = $query->paginate(15)->withQueryString();

        return view('pages.dynamic_dashboard', compact('fields', 'model', 'records'));
    }

    public function create($model)
    {
        $fields = $this->formService->getFormFields($model);
        $hasFiles = collect($fields)->contains('type', 'file');
        $record = null;

        return view('pages.dynamic_form', compact('fields', 'model', 'hasFiles', 'record'));
    }

    public function edit($model, $id)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);

        $record = $modelClass::findOrFail($id);
        $this->authorizeRowOwnership($record);

        $hasFiles = collect($fields)->contains('type', 'file');

        return view('pages.dynamic_form', compact('fields', 'model', 'hasFiles', 'record'));
    }

    public function show($model, $id)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);

        $record = $modelClass::findOrFail($id);
        $this->authorizeRowOwnership($record);

        return view('pages.dynamic_show', compact('fields', 'model', 'record'));
    }

    public function store(Request $request, $model)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);
        $tableName = (new $modelClass)->getTable();

        $validatedData = $request->validate($this->buildValidationRules($tableName, $fields, false, null));

        foreach ($fields as $field) {
            if ($field['type'] === 'file' && $request->hasFile($field['name'])) {
                // Organize uploads neatly inside folders named after each model string
                $validatedData[$field['name']] = $request->file($field['name'])->store('uploads/' . $model, 'public');
            } elseif ($field['type'] === 'checkbox') {
                $validatedData[$field['name']] = $request->has($field['name']);
            }
        }

        if (\Schema::hasColumn($tableName, 'user_id')) {
            $validatedData['user_id'] = Auth::id();
        }

        // Eloquent Model Save execution instance trigger
        $modelClass::create($validatedData);

        return redirect()->route('dynamic.form.index', $model)->with('success', 'Model item saved successfully!');
    }

    public function update(Request $request, $model, $id)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);

        $record = $modelClass::findOrFail($id);
        $this->authorizeRowOwnership($record);

        $validatedData = $request->validate($this->buildValidationRules($record->getTable(), $fields, true, $id));

        foreach ($fields as $field) {
            if ($field['type'] === 'file') {
                if ($request->hasFile($field['name'])) {
                    if (!empty($record->{$field['name']})) {
                        Storage::disk('public')->delete($record->{$field['name']});
                    }
                    $validatedData[$field['name']] = $request->file($field['name'])->store('uploads/' . $model, 'public');
                } else {
                    unset($validatedData[$field['name']]);
                }
            } elseif ($field['type'] === 'checkbox') {
                $validatedData[$field['name']] = $request->has($field['name']);
            }
        }

        $record->update($validatedData);

        return redirect()->route('dynamic.form.index', $model)->with('success', 'Model configuration updated!');
    }

    public function destroy($model, $id)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);

        $record = $modelClass::findOrFail($id);
        $this->authorizeRowOwnership($record);

        foreach ($fields as $field) {
            if ($field['type'] === 'file' && !empty($record->{$field['name']})) {
                Storage::disk('public')->delete($record->{$field['name']});
            }
        }

        $record->delete();

        return redirect()->route('dynamic.form.index', $model)->with('success', 'Model item deleted.');
    }

    private function authorizeRowOwnership($record): void
    {
        if (isset($record->user_id) && $record->user_id !== Auth::id()) {
            abort(403, 'Unauthorized model interaction sequence.');
        }
    }

    private function buildValidationRules(string $table, array $fields, bool $isUpdate = false, $id = null): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $fieldRules = [];
            $fieldRules[] = ($field['type'] === 'file' && $isUpdate) ? 'nullable' : ($field['required'] ? 'required' : 'nullable');

            if ($field['type'] === 'email') {
                $fieldRules[] = 'string|email|max:255';
            } elseif ($field['type'] === 'tel') {
                $fieldRules[] = 'string|min:7|max:20|regex:/^([0-9\s\-\+\(\)]*)$/';
            } elseif ($field['type'] === 'file') {
                $fieldRules[] = 'image|max:2048';
            } elseif ($field['type'] === 'select') {
                $fieldRules[] = 'in:' . implode(',', $field['options']);
            } elseif ($field['type'] === 'number') {
                $fieldRules[] = 'numeric';
            } else {
                $fieldRules[] = 'string';
            }

            if ($field['unique']) {
                $uniqueRule = Rule::unique($table, $field['name']);
                if ($isUpdate && $id !== null) {
                    $uniqueRule->ignore($id);
                }
                $fieldRules[] = $uniqueRule;
            }

            $rules[$field['name']] = $fieldRules;
        }
        return $rules;
    }
}


