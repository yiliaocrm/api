<?php

namespace Database\Seeders\Tenant\SceneFields;

class InventoryBatchsSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'InventoryBatchsIndex',
                'name'             => '入库日期',
                'table'            => 'inventory_batchs',
                'field'            => 'created_at',
                'field_type'       => 'timestamp',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between']
                ])
            ],
            [
                'page'             => 'InventoryBatchsIndex',
                'name'             => '所在仓库',
                'table'            => 'inventory_batchs',
                'field'            => 'warehouse_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/warehouse',
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'       => 'InventoryBatchsIndex',
                'name'       => '批号',
                'table'      => 'inventory_batchs',
                'field'      => 'batch_code',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ])
            ],
            [
                'page'             => 'InventoryBatchsIndex',
                'name'             => '生产日期',
                'table'            => 'inventory_batchs',
                'field'            => 'production_date',
                'field_type'       => 'date',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between']
                ])
            ],
            [
                'page'             => 'InventoryBatchsIndex',
                'name'             => '过期时间',
                'table'            => 'inventory_batchs',
                'field'            => 'expiry_date',
                'field_type'       => 'date',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between']
                ])
            ],
        ];
    }
}
