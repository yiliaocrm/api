<?php

namespace Database\Seeders\Tenant\SceneFields;

class ConsumableSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page' => 'ConsumableIndex',
                'name' => '单据编号',
                'table' => 'consumable',
                'field' => 'key',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                ]),
            ],
            [
                'page' => 'ConsumableIndex',
                'name' => '出库仓库',
                'table' => 'consumable',
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
                'page' => 'ConsumableIndex',
                'name' => '领料科室',
                'table' => 'consumable',
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
                'page' => 'ConsumableIndex',
                'name' => '耗材成本',
                'table' => 'consumable',
                'field' => 'amount',
                'field_type' => 'decimal',
                'component' => 'input',
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
                'page' => 'ConsumableIndex',
                'name' => '消费项目',
                'table' => 'consumable',
                'field' => 'product_name',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'ConsumableIndex',
                'name' => '备注信息',
                'table' => 'consumable',
                'field' => 'remark',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page' => 'ConsumableIndex',
                'name' => '领料人员',
                'table' => 'consumable',
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
                'page' => 'ConsumableIndex',
                'name' => '录单人员',
                'table' => 'consumable',
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
                'page' => 'ConsumableIndex',
                'name' => '登记时间',
                'table' => 'consumable',
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
