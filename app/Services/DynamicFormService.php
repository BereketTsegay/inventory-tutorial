<?php
namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;

class DynamicFormService
{
    public function resolveModelClass(string $modelName): string
    {
        $className = ucfirst(\Str::camel(\Str::singular($modelName)));
        $fullNamespace = "App\\Models\\" . $className;
        if (!class_exists($fullNamespace)) abort(404, "Model [{$className}] not found.");
        return $fullNamespace;
    }

    public function getFormFields(string $modelName): array
    {
        $modelClass = $this->resolveModelClass($modelName);
        $modelInstance = new $modelClass();
        $tableName = $modelInstance->getTable();

        $columns = Schema::getColumns($tableName);
        $indexes = Schema::getIndexes($tableName);
        $foreignKeys = Schema::getForeignKeys($tableName);
        $uniqueColumns = collect($indexes)->filter(fn($idx) => $idx['unique'])->flatMap(fn($idx) => $idx['columns'])->toArray();

        $formFields = [];

        // 1. Process database columns securely
        foreach ($columns as $column) {
            $name = $column['name'];
            if (in_array($name, [$modelInstance->getKeyName(), 'user_id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $isForeign = false;
            $options = [];
            $relationName = '';
            $isDependent = false;
            $parentFieldName = '';

            $fkInfo = collect($foreignKeys)->first(fn($fk) => in_array($name, $fk['columns']));

            if ($fkInfo || str_ends_with($name, '_id')) {
                $isForeign = true;
                $type = 'relation';
                $relationName = \Str::camel(str_replace('_id', '', $name));
                $targetTable = $fkInfo['foreign_table'] ?? \Str::plural(str_replace('_id', '', $name));
                $targetModelClass = "App\\Models\\" . ucfirst(\Str::camel(\Str::singular($targetTable)));

                if ($name === 'city_id' && collect($columns)->contains('name', 'country_id')) {
                    $isDependent = true;
                    $parentFieldName = 'country_id';
                } else if (class_exists($targetModelClass)) {
                    $options = $this->fetchModelOptions($targetModelClass);
                }
            } elseif (str_contains(strtolower($name), 'email')) {
                $type = 'email';
            } elseif (preg_match('/(phone|mobile|telephone|tel|whatsapp)/i', $name)) {
                $type = 'tel';
            } elseif (preg_match('/(image|photo|avatar|file)$/', $name)) {
                $type = 'file';
            } elseif (str_contains(strtolower($column['type_name']), 'enum')) {
                $type = 'select';
                $options = $this->getEnumOptions($tableName, $name);
            } else {
                $type = $this->mapDatabaseTypeToInput($column['type_name']);
            }

            $formFields[] = [
                'name'              => $name,
                'type'              => $type,
                'options'           => $options,
                'is_relation'       => $isForeign,
                'relation_name'     => $relationName,
                'is_dependent'      => $isDependent,
                'parent_field_name' => $parentFieldName,
                'required'          => !$column['nullable'],
                'unique'            => in_array($name, $uniqueColumns),
                'label'             => ucwords(str_replace('_', ' ', str_replace('_id', '', $name)))
            ];
        }

        // 2. STABLE RELATION CHECK: Safely map Many-to-Many relationships
        // Skip entirely if the model class doesn't exist or reflection breaks
        if (class_exists($modelClass)) {
            $reflection = new ReflectionClass($modelClass);

            // Core built-in framework methods we must NEVER run dynamic invoke calls against
            $ignoredMethods = [
                'hasNamedScope', 'boot', 'bootTraits', 'clearBootedModels', 'getGlobalScopes',
                'getGlobalScope', 'with', 'load', 'loadMissing', 'loadMorph', 'loadCount',
                'query', 'newQuery', 'newModelQuery', 'newQueryWithoutRelationships',
                'newQueryWithoutScopes', 'newQueryWithoutGlobalScopes', 'newQueryForRestoration',
                'user', 'getTable'
            ];

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // Ignore base framework methods, traits, internal configurations, and parameters
                if ($method->getDeclaringClass()->getName() !== $modelClass) continue;
                if ($method->getNumberOfParameters() > 0) continue;
                if (in_array($method->getName(), $ignoredMethods)) continue;

                try {
                    // Temporarily silence warning payloads during reflective scanning
                    $return = @$method->invoke($modelInstance);

                    if ($return instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                        $targetModelClass = get_class($return->getRelated());
                        $formFields[] = [
                            'name'          => $method->getName(),
                            'type'          => 'many_to_many',
                            'options'       => $this->fetchModelOptions($targetModelClass),
                            'is_relation'   => true,
                            'relation_name' => $method->getName(),
                            'required'      => false,
                            'unique'        => false,
                            'label'         => ucwords(str_replace('_', ' ', $method->getName()))
                        ];
                    }
                } catch (\Throwable $e) {
                    // Silently fall past methods that do not return functional relationships
                    continue;
                }
            }
        }

        return $formFields;
    }

    public function fetchModelOptions(string $modelClass, ?string $foreignKey = null, $parentValue = null): array
    {
        if (!class_exists($modelClass)) return [];

        try {
            $query = $modelClass::query();
            if ($foreignKey && $parentValue) {
                $query->where($foreignKey, $parentValue);
            }
            return $query->get()->map(fn($item) => [
                'id' => $item->getKey(),
                'label' => $item->name ?? $item->title ?? $item->label ?? "ID: " . $item->getKey()
            ])->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getEnumOptions(string $table, string $column): array
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column]);
            if (empty($columns)) return [];

            $type = $columns[0]->Type;
            preg_match('/^enum\((.*)\)$/', $type, $matches);
            return isset($matches) ? array_map(fn($value) => trim($value, "'"), explode(',', $matches)) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function mapDatabaseTypeToInput(string $dbType): string
    {
        return match (strtolower($dbType)) {
            'int', 'integer', 'bigint', 'smallint' => 'number',
            'boolean', 'tinyint'                   => 'checkbox',
            'text', 'mediumtext', 'longtext'       => 'textarea',
            'date'                                 => 'date',
            'datetime', 'timestamp'                => 'datetime-local',
            default                                => 'text',
        };
    }
}
