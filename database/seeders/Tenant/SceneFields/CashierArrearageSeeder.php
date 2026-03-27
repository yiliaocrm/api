<?php

namespace Database\Seeders\Tenant\SceneFields;

class CashierArrearageSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page' => 'CashierArrearageIndex',
                'name' => '项目名称',
                'table' => 'cashier_arrearage',
                'field' => 'product_name',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page' => 'CashierArrearageIndex',
                'name' => '套餐名称',
                'table' => 'cashier_arrearage',
                'field' => 'package_name',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page' => 'CashierArrearageIndex',
                'name' => '单据状态',
                'table' => 'cashier_arrearage',
                'field' => 'status',
                'field_type' => 'tinyint',
                'component' => 'select',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                        'filterable' => true,
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.cashier_arrearage.status'),
                ]),
            ],
        ];
    }
}
