<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportPurchaseDetailSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'ReportPurchaseDetail',
                'name'             => '所在仓库',
                'table'            => 'purchase_detail',
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
                'page'             => 'ReportPurchaseDetail',
                'name'             => '单据日期',
                'table'            => 'purchase_detail',
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
                'page'             => 'ReportPurchaseDetail',
                'name'             => '过期时间',
                'table'            => 'purchase_detail',
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
                    ['text' => '区间', 'value' => 'between'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'             => 'ReportPurchaseDetail',
                'name'             => '生产日期',
                'table'            => 'purchase_detail',
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
                    ['text' => '区间', 'value' => 'between'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'ReportPurchaseDetail',
                'name'       => '单据编号',
                'table'      => 'purchase_detail',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'       => 'ReportPurchaseDetail',
                'name'       => '规格型号',
                'table'      => 'purchase_detail',
                'field'      => 'specs',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'       => 'ReportPurchaseDetail',
                'name'       => '生产厂商',
                'table'      => 'purchase_detail',
                'field'      => 'manufacturer_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'       => 'ReportPurchaseDetail',
                'name'       => '批准文号',
                'table'      => 'purchase_detail',
                'field'      => 'approval_number',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'       => 'ReportPurchaseDetail',
                'name'       => '批号',
                'table'      => 'purchase_detail',
                'field'      => 'batch_code',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'       => 'ReportPurchaseDetail',
                'name'       => 'SN码',
                'table'      => 'purchase_detail',
                'field'      => 'sncode',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'       => 'ReportPurchaseDetail',
                'name'       => '备注',
                'table'      => 'purchase_detail',
                'field'      => 'remark',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'ReportPurchaseDetail',
                'name'             => '商品类别',
                'table'            => 'purchase_detail',
                'field'            => 'type_id',
                'api'              => '/cache/goods-type?cascader=1',
                'field_type'       => 'int',
                'component'        => 'cascader',
                'component_params' => json_encode([
                    'props' => [
                        'props'      => [
                            'label'         => 'name',
                            'value'         => 'id',
                            'checkStrictly' => true,
                        ],
                        'clearable'  => true,
                        'filterable' => true
                    ]
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_goods.type_id IN (select id from cy_goods_type where tree LIKE CONCAT((SELECT tree FROM cy_goods_type WHERE id = ?), '-%') OR cy_goods_type.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => '<>',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_goods.type_id NOT IN (select id from cy_goods_type where tree LIKE CONCAT((SELECT tree FROM cy_goods_type WHERE id = ?), '-%') OR cy_goods_type.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ]
                ])
            ]
        ];
    }
}
