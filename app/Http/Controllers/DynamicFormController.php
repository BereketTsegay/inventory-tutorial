<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DynamicFormService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema; // <--- CRITICAL: Add this line
use Illuminate\Support\Facades\DB;

class DynamicFormController extends Controller
{
    protected $formService;

    public function __construct(DynamicFormService $formService)
    {
        $this->formService = $formService;
    }

    // New API Endpoint: Handles dependent dropdown options updates via AJAX
    public function getChildOptions(Request $request, $model)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $parentField = $request->query('parent_field'); // e.g., 'country_id'
        $parentValue = $request->query('parent_value'); // Selected Country ID index integer

        // Map column details back to target classes (e.g. city_id points to City model)
        $targetModelName = ucfirst(\Str::camel(\Str::singular((new $modelClass)->getTable())));
        $options = $this->formService->fetchModelOptions($modelClass, $parentField, $parentValue);

        return response()->json($options);
    }

    public function index(Request $request, $model)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);

        $modelInstance = new $modelClass();
        $tableName = $modelInstance->getTable();

        // 1. Identify which relationships are safe and valid to load
        $relationsToLoad = collect($fields)
            ->where('is_relation', true)
            ->pluck('relation_name')
            ->filter(fn($rel) => !empty($rel) && method_exists($modelInstance, $rel))
            ->toArray();

        // 2. FIX: Only execute ::with() if the array has relations, avoiding "connection() on null"
        $query = !empty($relationsToLoad) ? $modelClass::with($relationsToLoad) : $modelClass::query();

        if (\Schema::hasColumn($tableName, 'user_id')) {
            $query->where('user_id', \Auth::id());
        }

        // Global Search Filters
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

        // Drop-down filters
        foreach ($fields as $field) {
            if (in_array($field['type'], ['select', 'relation']) && $request->filled('filter_' . $field['name'])) {
                $query->where($field['name'], $request->input('filter_' . $field['name']));
            }
        }

        $records = $query->paginate(15)->withQueryString();

        return view('pages.dynamic_dashboard', compact('fields', 'model', 'records'));
    }



    public function store(Request $request, $model)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);

        // Filter out structural generic many_to_many types during validation steps
        $scalarFields = collect($fields)->where('type', '!=', 'many_to_many')->toArray();
        $validatedData = $request->validate($this->buildValidationRules((new $modelClass)->getTable(), $scalarFields));

        foreach ($scalarFields as $field) {
            if ($field['type'] === 'file' && $request->hasFile($field['name'])) {
                $validatedData[$field['name']] = $request->file($field['name'])->store('uploads/' . $model, 'public');
            } elseif ($field['type'] === 'checkbox') {
                $validatedData[$field['name']] = $request->has($field['name']);
            }
        }

        $record = $modelClass::create($validatedData);

        // Many-to-Many Syncing logic
        foreach ($fields as $field) {
            if ($field['type'] === 'many_to_many' && $request->has($field['name'])) {
                $relation = $field['relation_name'];
                $record->$relation()->sync($request->input($field['name']));
            }
        }

        return redirect()->route('dynamic.form.index', $model)->with('success', 'Model entries saved.');
    }

    public function update(Request $request, $model, $id)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);
        $record = $modelClass::findOrFail($id);

        $scalarFields = collect($fields)->where('type', '!=', 'many_to_many')->toArray();
        $validatedData = $request->validate($this->buildValidationRules($record->getTable(), $scalarFields, true, $id));

        foreach ($scalarFields as $field) {
            if ($field['type'] === 'file') {
                if ($request->hasFile($field['name'])) {
                    if (!empty($record->{$field['name']})) Storage::disk('public')->delete($record->{$field['name']});
                    $validatedData[$field['name']] = $request->file($field['name'])->store('uploads/' . $model, 'public');
                } else {
                    unset($validatedData[$field['name']]);
                }
            } elseif ($field['type'] === 'checkbox') {
                $validatedData[$field['name']] = $request->has($field['name']);
            }
        }

        $record->update($validatedData);

        // Sync pivot values
        foreach ($fields as $field) {
            if ($field['type'] === 'many_to_many') {
                $relation = $field['relation_name'];
                $record->$relation()->sync($request->input($field['name'], []));
            }
        }

        return redirect()->route('dynamic.form.index', $model)->with('success', 'Model updated!');
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
            }
            elseif ($field['type'] === 'number') {
                $fieldRules[] = 'numeric';
            }
            else {
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


