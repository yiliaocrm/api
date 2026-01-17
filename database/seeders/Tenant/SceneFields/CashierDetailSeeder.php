<?php

namespace Database\Seeders\Tenant\SceneFields;

class CashierDetailSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'       => 'CashierDetailIndex',
                'name'       => '收费单号',
                'table'      => 'cashier_detail',
                'field'      => 'cashier_id',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'             => 'CashierDetailIndex',
                'name'             => '业务类型',
                'table'            => 'cashier_detail',
                'field'            => 'cashierable_type',
                'field_type'       => 'varchar',
                'component'        => 'select',
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
                'component_params' => json_encode([
                    'props'   => [
                        'clearable'  => true,
                        'filterable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.cashier.cashierable_type')
                ]),
            ],
            [
                'page'         => 'CashierDetailIndex',
                'name'         => '项目名称/物品名称',
                'table'        => 'cashier_detail',
                'field'        => 'product_name',
                'field_type'   => 'varchar',
                'component'    => 'input',
                'operators'    => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ]),
                'query_config' => json_encode([
                    [
                        'operator' => '=',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_cashier_detail.product_name LIKE ? OR cy_cashier_detail.goods_name LIKE ?)",
                                'bindings' => [
                                    '%{$value}%',
                                    '%{$value}%'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => '<>',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_cashier_detail.product_name NOT LIKE ? OR cy_cashier_detail.goods_name NOT LIKE ?)",
                                'bindings' => [
                                    '%{$value}%',
                                    '%{$value}%'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
            [
                'page'       => 'CashierDetailIndex',
                'name'       => '套餐名称',
                'table'      => 'cashier_detail',
                'field'      => 'package_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => 'like'],
                    ['text' => '不等于', 'value' => 'not like'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ]),
            ],
            [
                'page'             => 'CashierDetailIndex',
                'name'             => '结算科室',
                'table'            => 'cashier_detail',
                'field'            => 'department_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/departments',
                'component_params' => json_encode([
                    'props' => [
                        'clearable'  => true,
                        'filterable' => true
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            [
                'page'       => 'CashierDetailIndex',
                'name'       => '收银人员',
                'table'      => 'cashier_detail',
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
        ];
    }
}
