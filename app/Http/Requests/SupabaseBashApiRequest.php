<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SupabaseBashApiRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        $baseRules = [
            'table_name' => [
                'required',
                'string',
            ],
            'level' => [
                'required',
                'integer',
            ],
           'data' => [
               'required',
               'array',
           ],
        ];
        $prefixedDynamicRules = [];
        $tableName = $this->getTableName();
        if (!$tableName || trim($tableName) == '') {
            return $baseRules;
        }
        $dynamicTableRules = $this->getDynamicRulesForTable($tableName);
        foreach ($dynamicTableRules as $field => $rules) {
            $prefixedDynamicRules['data.*.' . $field] = $rules;
        }
        return array_merge($baseRules, $prefixedDynamicRules);
    }

    public function getTableData(): array
    {
        return $this->input('data', []);
    }

    public function getTableName(): string
    {
        return $this->input('table_name', '');
    }

    public function getLevel(): int
    {
        return $this->input('level');
    }

    protected function getDynamicRulesForTable($tableName): array
    {
        $jsonFilePath = __DIR__ . '/supabase_validation_rules.json';

        if (!File::exists($jsonFilePath)) {
            Log::error("Validation rules JSON file not found at: " . $jsonFilePath);
            return [];
        }

        $jsonContents = File::get($jsonFilePath);
        $allRules = json_decode($jsonContents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Error decoding validation rules JSON: " . json_last_error_msg());
            return [];
        }

        return $allRules[$tableName] ?? $allRules['default'] ?? [];
    }

}
