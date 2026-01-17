<?php

namespace Database\Seeders\Tenant\SceneFields;

class PurchaseReturnIndexSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'       => 'PurchaseReturnIndex',
                'name'       => '单据号',
                'table'      => 'purchase_return',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'PurchaseReturnIndex',
                'name'             => '单据状态',
                'table'            => 'purchase_return',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.purchase.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'PurchaseReturnIndex',
                'name'             => '单据日期',
                'table'            => 'purchase_return',
                'field'            => 'date',
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
                'page'             => 'PurchaseReturnIndex',
                'name'             => '退货仓库',
                'table'            => 'purchase_return',
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
                'page'       => 'PurchaseReturnIndex',
                'name'       => '经办人员',
                'table'      => 'purchase_return',
                'field'      => 'user_id',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'PurchaseReturnIndex',
                'name'       => '审核人员',
                'table'      => 'purchase_return',
                'field'      => 'check_user',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'PurchaseReturnIndex',
                'name'       => '录单人员',
                'table'      => 'purchase_return',
                'field'      => 'create_user_id',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'PurchaseReturnIndex',
                'name'       => '退货厂商',
                'table'      => 'purchase_return',
                'field'      => 'supplier_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like']
                ]),
            ],
            [
                'page'             => 'PurchaseReturnIndex',
                'name'             => '录单日期',
                'table'            => 'purchase_return',
                'field'            => 'created_at',
                'field_type'       => 'datetime',
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
                'page'             => 'PurchaseReturnIndex',
                'name'             => '审核日期',
                'table'            => 'purchase_return',
                'field'            => 'check_time',
                'field_type'       => 'datetime',
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
                'page'       => 'PurchaseReturnIndex',
                'name'       => '退货备注',
                'table'      => 'purchase_return',
                'field'      => 'remark',
                'field_type' => 'text',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
        ];
    }
}
