<?php

namespace Database\Seeders\Tenant\SceneFields;

class InventoryCheckSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page' => 'InventoryCheckIndex',
                'name' => '单据号',
                'table' => 'inventory_checks',
                'field' => 'key',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '单据日期',
                'table' => 'inventory_checks',
                'field' => 'date',
                'field_type' => 'date',
                'component' => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type' => 'date',
                        'value-format' => 'YYYY-MM-DD',
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '单据状态',
                'table' => 'inventory_checks',
                'field' => 'status',
                'field_type' => 'tinyint',
                'component' => 'select',
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.inventory_checks.status'),
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '盘点仓库',
                'table' => 'inventory_checks',
                'field' => 'warehouse_id',
                'field_type' => 'int',
                'component' => 'select',
                'api' => '/cache/warehouse',
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '盘点科室',
                'table' => 'inventory_checks',
                'field' => 'department_id',
                'field_type' => 'int',
                'component' => 'select',
                'api' => '/cache/departments',
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                        'filterable' => true,
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '录单人员',
                'table' => 'inventory_checks',
                'field' => 'create_user_id',
                'field_type' => 'int',
                'component' => 'user',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '经办人员',
                'table' => 'inventory_checks',
                'field' => 'user_id',
                'field_type' => 'int',
                'component' => 'user',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '审核人员',
                'table' => 'inventory_checks',
                'field' => 'check_user',
                'field_type' => 'int',
                'component' => 'user',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '盘点备注',
                'table' => 'inventory_checks',
                'field' => 'remark',
                'field_type' => 'text',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '审核日期',
                'table' => 'inventory_checks',
                'field' => 'check_time',
                'field_type' => 'datetime',
                'component' => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type' => 'date',
                        'value-format' => 'YYYY-MM-DD',
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ]),
            ],
            [
                'page' => 'InventoryCheckIndex',
                'name' => '录单日期',
                'table' => 'inventory_checks',
                'field' => 'created_at',
                'field_type' => 'timestamp',
                'component' => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type' => 'date',
                        'value-format' => 'YYYY-MM-DD',
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ]),
            ],
        ];
    }
}
