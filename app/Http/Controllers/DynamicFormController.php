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


        // NEW API ACTION: Drops a single item element out of the JSON cluster string mapping array
    public function removeSingleJsonImage(Request $request, $model, $id)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $record = $modelClass::findOrFail($id);
        
        $fieldName = $request->input('field_name'); // Target column e.g. 'image'
        $targetIndex = $request->input('index_id'); // Target array offset index integer
        
        $paths = json_decode($record->{$fieldName} ?? '[]', true);
        
        if (isset($paths[$targetIndex])) {
            // Drop physical asset file off disk partitions
            Storage::disk('public')->delete($paths[$targetIndex]);
            
            // Re-index array sequence tracking indicators
            unset($paths[$targetIndex]);
            $updatedPaths = array_values($paths);
            
            // Save modified serialized string data array mapping changes
            $record->update([
                $fieldName => !empty($updatedPaths) ? json_encode($updatedPaths) : null
            ]);
            
            return response()->json(['success' => true, 'message' => 'Single image erased.']);
        }
        
        return response()->json(['success' => false, 'message' => 'Element index mapping not located.'], 400);
    }

    public function store(Request $request, $model)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);
        $tableName = (new $modelClass)->getTable();

        $scalarFields = collect($fields)->where('type', '!=', 'many_to_many')->toArray();
        
        // 1. LIMIT ENFORCEMENT CHECK: Quick validation baseline test before deep storage routines execute
        foreach ($scalarFields as $field) {
            if ($field['type'] === 'json_array' && $request->hasFile($field['name'])) {
                if (count($request->file($field['name'])) > 5) {
                    return redirect()->back()->withErrors([$field['name'] => 'You cannot upload more than 5 files total onto this input.'])->withInput();
                }
            }
        }

        $validatedData = $request->validate($this->buildValidationRules($tableName, $scalarFields));

        foreach ($scalarFields as $field) {
            if ($field['type'] === 'json_array') {
                $storedPaths = [];
                if ($request->hasFile($field['name'])) {
                    foreach ($request->file($field['name']) as $file) {
                        $storedPaths[] = $file->store('uploads/' . $model, 'public');
                    }
                    $validatedData[$field['name']] = json_encode($storedPaths);
                }
            }
            // Standard single file block fallback handles
            elseif ($field['type'] === 'file' && $request->hasFile($field['name'])) {
                $validatedData[$field['name']] = $request->file($field['name'])->store('uploads/' . $model, 'public');
            }
        }

        $modelClass::create($validatedData);
        return redirect()->route('dynamic.form.index', $model)->with('success', 'Saved successfully.');
    }

    public function update(Request $request, $model, $id)
    {
        $modelClass = $this->formService->resolveModelClass($model);
        $fields = $this->formService->getFormFields($model);
        $record = $modelClass::findOrFail($id);
        $tableName = $record->getTable();

        $scalarFields = collect($fields)->where('type', '!=', 'many_to_many')->toArray();

        // 2. LIMIT ENFORCEMENT CHECK (UPDATE): Calculates existing paths + new items to keep the total count <= 5
        foreach ($scalarFields as $field) {
            if ($field['type'] === 'json_array' && $request->hasFile($field['name'])) {
                $existingCount = count(json_decode($record->{$field['name']} ?? '[]', true));
                $incomingCount = count($request->file($field['name']));
                
                if (($existingCount + $incomingCount) > 5) {
                    return redirect()->back()->withErrors([$field['name'] => "Adding these would bring the total count to " . ($existingCount + $incomingCount) . ". Max allowed images allocation is 5 total."])->withInput();
                }
            }
        }

        $validatedData = $request->validate($this->buildValidationRules($tableName, $scalarFields, true, $id));

        foreach ($scalarFields as $field) {
            if ($field['type'] === 'json_array') {
                if ($request->hasFile($field['name'])) {
                    $existingPaths = json_decode($record->{$field['name']} ?? '[]', true) ?: [];
                    
                    // Append new items into the existing JSON array configuration sequence
                    foreach ($request->file($field['name']) as $file) {
                        $existingPaths[] = $file->store('uploads/' . $model, 'public');
                    }
                    $validatedData[$field['name']] = json_encode($existingPaths);
                } else {
                    unset($validatedData[$field['name']]); // Keep old collection untouched if no files are added
                }
            }
            // Fallback rules block for single scalar files type elements
            elseif ($field['type'] === 'file' && $request->hasFile($field['name'])) {
                if (!empty($record->{$field['name']})) Storage::disk('public')->delete($record->{$field['name']});
                $validatedData[$field['name']] = $request->file($field['name'])->store('uploads/' . $model, 'public');
            }
        }

        $record->update($validatedData);
        return redirect()->route('dynamic.form.index', $model)->with('success', 'Model entries updated!');
    }


    private function buildValidationRules(string $table, array $fields, bool $isUpdate = false, $id = null): array
{
    $rules = [];

    foreach ($fields as $field) {
        $fieldRules = [];
        
        // 1. Core structural requirement checks
        if ($field['type'] === 'file' && $isUpdate) {
            $fieldRules[] = 'nullable';
        } else {
            $fieldRules[] = $field['required'] ? 'required' : 'nullable';
        }

        // 2. Map structural input overlays into split, explicit rule elements
        if ($field['type'] === 'json_array') {
            // If handling multi-file updates, validate items individually inside loop variables
            if (request()->hasFile($field['name'])) {
                // FIX: Separated clean distinct array values
                $rules[$field['name'] . '.*'] = ['image', 'max:2048'];
                continue; // Skips processing scalar rules below for multi-files
            } else {
                $fieldRules[] = 'json';
            }
        } 
        elseif ($field['type'] === 'file') {
            // FIX: Separated 'image' and 'max:2048' into distinct array strings
            $fieldRules[] = 'image';
            $fieldRules[] = 'max:2048';
        } 
        elseif ($field['type'] === 'email') {
            $fieldRules[] = 'string';
            $fieldRules[] = 'email';
            $fieldRules[] = 'max:255';
        } 
        elseif ($field['type'] === 'tel') {
            $fieldRules[] = 'string';
            $fieldRules[] = 'min:7';
            $fieldRules[] = 'max:20';
            $fieldRules[] = 'regex:/^([0-9\s\-\+\(\)]*)$/';
        } 
        elseif ($field['type'] === 'select' || $field['type'] === 'relation') {
            if (!empty($field['options'])) {
                $allowedIds = collect($field['options'])->pluck('id')->toArray();
                if (!empty($allowedIds)) {
                    $fieldRules[] = 'in:' . implode(',', $allowedIds);
                }
            }
        } 
        elseif ($field['type'] === 'number') {
            $fieldRules[] = 'numeric';
        } 
        else {
            $fieldRules[] = 'string';
        }

        // 3. Append fluent unqiue constraints
        if (!empty($field['unique'])) {
            $uniqueRule = \Illuminate\Validation\Rule::unique($table, $field['name']);
            if ($isUpdate && $id !== null) {
                $uniqueRule->ignore($id);
            }
            $fieldRules[] = $uniqueRule;
        }

        $rules[$field['name']] = $fieldRules;
    }

    return $rules;
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

    
}


