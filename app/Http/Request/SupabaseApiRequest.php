<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Importa Rule para validaciones condicionales o de existencia

class SupabaseApiRequest extends FormRequest
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
           'data' => [
               'required',
               'array',
           ],
        ];
        $tableName = $this->getTableName();
        $dynamicTableRules = $this->getDynamicRulesForTable($tableName);
        $prefixedDynamicRules = [];
        foreach ($dynamicTableRules as $field => $rules) {
            $prefixedDynamicRules['data.' . $field] = $rules;
        }
        return array_merge($baseRules, $prefixedDynamicRules);
    }

    public function getTableData(): array
    {
        return $this->except(['table_name']);
    }

    public function getTableName(): string
    {
        return $this->input('table_name');
    }

    protected function getDynamicRulesForTable($tableName): array
    {
        switch ($tableName) {
            case 'products':
                return [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'price' => 'required|numeric|min:0',
                    'image_url' => 'nullable|url',
                ];
            case 'categories':
                return [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                ];
        }
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|url',
        ];
    }

}
