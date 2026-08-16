<?php
namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DynamicFormService
{
    /**
     * Resolve fully qualified Class Names string targets from clean route tags.
     */
    public function resolveModelClass(string $modelName): string
    {
        // Converts "product_categories" or "productCategory" directly to "ProductCategory"
        $className = ucfirst(\Str::camel(\Str::singular($modelName)));
        $fullNamespace = "App\\Models\\" . $className;

        if (!class_exists($fullNamespace)) {
            abort(404, "Target Model [{$className}] class structure is not initialized.");
        }

        return $fullNamespace;
    }

    public function getFormFields(string $modelName): array
    {
        $modelClass = $this->resolveModelClass($modelName);
        $modelInstance = new $modelClass();
        $tableName = $modelInstance->getTable(); // Extract actual database table layout target

        $columns = Schema::getColumns($tableName);
        $indexes = Schema::getIndexes($tableName);

        $uniqueColumns = collect($indexes)
            ->filter(fn($index) => $index['unique'] === true)
            ->flatMap(fn($index) => $index['columns'])
            ->toArray();

        $formFields = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            // Skip primary and timestamp fields
            if (in_array($name, [$modelInstance->getKeyName(), 'created_at', 'updated_at', 'deleted_at','slug'])) {
                continue;
            }

            if (str_contains(strtolower($name), 'email')) {
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
                'name'     => $name,
                'type'     => $type,
                'options'  => $options ?? [],
                'required' => !$column['nullable'],
                'unique'   => in_array($name, $uniqueColumns),
                'label'    => ucwords(str_replace('_', ' ', $name))
            ];
        }

        return $formFields;
    }

    private function getEnumOptions(string $table, string $column): array
    {
        $type = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column])[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);
        return isset($matches[1]) ? array_map(fn($value) => trim($value, "'"), explode(',', $matches[1])) : [];
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
