<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DynamicFormService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DynamicFormController extends Controller
{
    protected $formService;

    public function __construct(DynamicFormService $formService)
    {
        $this->formService = $formService;
    }

    public function index(Request $request, $table)
    {
        $fields = $this->formService->getFormFields($table);
        if (empty($fields)) abort(404);

        $query = DB::table($table);

        // 1. Structural Row Guard Filter: Enforce standard row isolation rules
        // Assumes your guarded tables contain a user tracking column (e.g., 'user_id')
        if (Schema::hasColumn($table, 'user_id')) {
            $query->where('user_id', Auth::id());
        }

        // 2. Global Text Search
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($subQuery) use ($fields, $searchTerm) {
                foreach ($fields as $field) {
                    if (in_array($field['type'], ['text', 'textarea', 'number'])) {
                        $subQuery->orWhere($field['name'], 'LIKE', $searchTerm);
                    }
                }
            });
        }

        // 3. Dynamic Enum/Select Filters
        foreach ($fields as $field) {
            if ($field['type'] === 'select' && $request->filled('filter_' . $field['name'])) {
                $query->where($field['name'], $request->input('filter_' . $field['name']));
            }
        }

        // Fetch records with query parameters preserved in pagination links
        $records = $query->paginate(15)->withQueryString();

        return view('pages.dynamic_dashboard', compact('fields', 'table', 'records'));
    }

    // 1. Create Render
    public function create($table)
    {
        $fields = $this->formService->getFormFields($table);
        if (empty($fields)) abort(404, "Table schema not found.");

        $hasFiles = collect($fields)->contains('type', 'file');
        $record = null; // No existing record in create state

        return view('pages.dynamic_form', compact('fields', 'table', 'hasFiles', 'record'));
    }

    // 2. Edit Render
    public function edit($table, $id)
    {
        $fields = $this->formService->getFormFields($table);
        if (empty($fields)) abort(404, "Table schema not found.");

        $record = DB::table($table)->where('id', $id)->first();
        if (!$record) abort(404, "Record not found.");

        $hasFiles = collect($fields)->contains('type', 'file');

        return view('pages.dynamic_form', compact('fields', 'table', 'hasFiles', 'record'));
    }

    // 3. Store Logic (Create Action)
    public function store(Request $request, $table)
    {
        $fields = $this->formService->getFormFields($table);
        $validatedData = $request->validate($this->buildValidationRules($table, $fields, true, null));

        foreach ($fields as $field) {
            if ($field['type'] === 'file' && $request->hasFile($field['name'])) {
                $image = $request->file($field['name']);
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/'.$table), $imageName);
                $validatedData[$field['name']] = 'uploads/' . $table . '/' . $imageName;
            } elseif ($field['type'] === 'checkbox') {
                $validatedData[$field['name']] = $request->has($field['name']);
            }
        }

        DB::table($table)->insert($validatedData);

        $notification = array(
            'message' => 'Record saved successfully!',
            'alert-type' => 'success'
        );
        return redirect()->route('dynamic.index', $table)->with($notification);
    }

    // 4. Update Logic (Edit Action)
    public function update(Request $request, $table, $id)
    {
        $fields = $this->formService->getFormFields($table);
        $record = DB::table($table)->where('id', $id)->first();
        if (!$record) abort(404);

        // Validation rules are less strict for files on update (nullable if not re-uploaded)
        $rules = $this->buildValidationRules($table, $fields, true, $id);
        $validatedData = $request->validate($rules);

        foreach ($fields as $field) {
            if ($field['type'] === 'file') {
                if ($request->hasFile($field['name'])) {
                    // Delete old file asset if it exists
                    if (!empty($record->{$field['name']})) {
                        Storage::disk('public')->delete($record->{$field['name']});
                    }
                    $image = $request->file($field['name']);
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $image->move(public_path('uploads/'.$table), $imageName);
                    $validatedData[$field['name']] = 'uploads/' . $table . '/' . $imageName;
                } else {
                    // Retain old value if no new asset is loaded
                    unset($validatedData[$field['name']]);
                }
            } elseif ($field['type'] === 'checkbox') {
                $validatedData[$field['name']] = $request->has($field['name']);
            }
        }

        DB::table($table)->where('id', $id)->update($validatedData);
        $notification = array(
            'message' => 'Record updated successfully!',
            'alert-type' => 'success'
        );
        return redirect()->route('dynamic.index', $table)->with($notification);
    }

    // 5. Destroy Logic (Delete Action)
    public function destroy($table, $id)
    {
        $fields = $this->formService->getFormFields($table);
        $record = DB::table($table)->where('id', $id)->first();
        if (!$record) abort(404);

        // Clear associated media assets from disk
        foreach ($fields as $field) {
            if ($field['type'] === 'file' && !empty($record->{$field['name']})) {
                Storage::disk('public')->delete($record->{$field['name']});
            }
        }

        DB::table($table)->where('id', $id)->delete();
        $notification = array(
            'message' => 'Record deleted successfully!',
            'alert-type' => 'success'
        );
        return redirect()->route('dynamic.index', $table)->with($notification);
    }

    // Helper method to keep dynamic validation logic centralized
    private function buildValidationRules(string $table, array $fields, bool $isUpdate = false, $id = null): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $fieldRules = [];

            // Set required or nullable properties
            if ($field['type'] === 'file' && $isUpdate) {
                $fieldRules[] = 'nullable';
            } else {
                $fieldRules[] = $field['required'] ? 'required' : 'nullable';
            }

            // Set basic datatype constraints
            if ($field['type'] === 'file') {
                $fieldRules[] = 'image';
                $fieldRules[] = 'max:2048';
            } elseif ($field['type'] === 'select') {
                $fieldRules[] = 'in:' . implode(',', $field['options']);
            } elseif ($field['type'] === 'number') {
                $fieldRules[] = 'numeric';
            } else {
                $fieldRules[] = 'string';
            }

            // Inject Fluent Unique validation objects dynamically
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

    // 6. Singular Record Detailed Inspector Render
    public function show($table, $id)
    {
        $fields = $this->formService->getFormFields($table);
        if (empty($fields)) abort(404);

        $record = DB::table($table)->where('id', $id)->first();
        if (!$record) abort(404);

        // Enforce row-guard infrastructure security check
        // $this->authorizeRowOwnership($table, $record);

        return view('pages.dynamic_show', compact('fields', 'table', 'record'));
    }
}

