<?php
namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DynamicFormService
{
    public function getFormFields(string $tableName): array
    {
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        $columns = Schema::getColumns($tableName);

        // Fetch all indexes defined on this specific table
        $indexes = Schema::getIndexes($tableName);

        // Extract names of columns that are part of a unique index
        $uniqueColumns = collect($indexes)
            ->filter(fn($index) => $index['unique'] === true)
            ->flatMap(fn($index) => $index['columns'])
            ->toArray();

        $formFields = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            if (preg_match('/(image|photo|avatar|file)$/', $name)) {
                $type = 'file';
                $options = [];
            } elseif (str_contains(strtolower($column['type_name']), 'enum')) {
                $type = 'select';
                $options = $this->getEnumOptions($tableName, $name);
            } else {
                $type = $this->mapDatabaseTypeToInput($column['type_name']);
                $options = [];
            }

            $formFields[] = [
                'name'     => $name,
                'type'     => $type,
                'options'  => $options,
                'required' => !$column['nullable'],
                'unique'   => in_array($name, $uniqueColumns), // Appended flag
                'label'    => ucwords(str_replace('_', ' ', $name))
            ];
        }

        return $formFields;
    }

    private function getEnumOptions(string $table, string $column): array
    {
        $type = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column])[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);

        if (!isset($matches[1])) {
            return [];
        }

        return array_map(fn($value) => trim($value, "'"), explode(',', $matches[1]));
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
