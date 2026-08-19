<?php
namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;

class DynamicFormService
{


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

        foreach ($columns as $column) {
            $name = $column['name'];
            if (in_array($name, [$modelInstance->getKeyName(), 'user_id', 'created_at', 'updated_at', 'deleted_at','slug'])) {
                continue;
            }

            $isForeign = false;
            $options = [];
            $relationName = null;
            $isDependent = false;
            $parentFieldName = '';

            $fkInfo = collect($foreignKeys)->first(fn($fk) => in_array($name, $fk['columns']));

            // Normalize relationship detection flags
            if ($fkInfo || str_ends_with($name, '_id')) {
                $isForeign = true;
                $type = 'relation';

                // CRITICAL: Clean transformation matching Eloquent conventions
                // category_id becomes "category", parent_category_id becomes "parentCategory"
                $relationName = \Str::camel(str_replace('_id', '', $name));

                $targetTable = $fkInfo['foreign_table'] ?? \Str::plural(str_replace('_id', '', $name));
                $targetModelName = ucfirst(\Str::camel(\Str::singular($targetTable)));
                $targetModelClass = "App\\Models\\" . $targetModelName;

                if ($name === 'city_id' && collect($columns)->contains('name', 'country_id')) {
                    $isDependent = true;
                    $parentFieldName = 'country_id';
                }

                if (class_exists($targetModelClass)) {
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
                'name'              => $name, // Always category_id
                'type'              => $type,
                'options'           => $options,
                'is_relation'       => $isForeign,
                'relation_name'     => $relationName, // Always "category"
                'is_dependent'      => $isDependent,
                'parent_field_name' => $parentFieldName,
                'required'          => !$column['nullable'],
                'unique'            => in_array($name, $uniqueColumns),
                'label'             => ucwords(str_replace('_', ' ', str_replace('_id', '', $name)))
            ];
        }

        // Many-to-Many Reflections
        if (class_exists($modelClass)) {
            $reflection = new ReflectionClass($modelClass);
            $ignoredMethods = ['hasNamedScope', 'boot', 'bootTraits', 'clearBootedModels', 'getGlobalScopes', 'user', 'getTable'];

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $modelClass) continue;
                if ($method->getNumberOfParameters() > 0) continue;
                if (in_array($method->getName(), $ignoredMethods)) continue;

                try {
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
                'label' => $item->name ?? $item->title ?? $item->label ?? $item->username ?? "ID: " . $item->getKey()
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
            preg_match('/^enum\((.*)\)$/', $columns[0]->Type, $matches);
            return isset($matches) ? array_map(fn($value) => trim($value, "'"), explode(',', $matches)) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }


}
