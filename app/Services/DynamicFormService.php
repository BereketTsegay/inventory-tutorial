<?php
namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DynamicFormService
{
    public function getFormFields(string $tableName): array
    {
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        $columns = Schema::getColumns($tableName);
        $formFields = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // 1. Identify File/Image Upload fields based on naming conventions
            if (preg_match('/(image|photo|avatar|file)$/', $name)) {
                $type = 'file';
                $options = [];
            }
            // 2. Identify and parse ENUM column properties
            elseif (str_contains(strtolower($column['type_name']), 'enum')) {
                $type = 'select';
                $options = $this->getEnumOptions($tableName, $name);
            }
            // 3. Fallback to standard scalar mapping
            else {
                $type = $this->mapDatabaseTypeToInput($column['type_name']);
                $options = [];
            }

            $formFields[] = [
                'name'     => $name,
                'type'     => $type,
                'options'  => $options,
                'required' => !$column['nullable'],
                'label'    => ucwords(str_replace('_', ' ', $name))
            ];
        }

        return $formFields;
    }

    private function getEnumOptions(string $table, string $column): array
    {
        // Query the database driver directly to fetch enum string configurations
        $type = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column])[0]->Type;

        // Extract array entries inside the enum('val1','val2') string wrapper
        preg_match('/^enum\((.*)\)$/', $type, $matches);

        if (!isset($matches[1])) {
            return [];
        }

        return array_map(function($value) {
            return trim($value, "'");
        }, explode(',', $matches[1]));
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
