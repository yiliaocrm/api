<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportConsultantOrderSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            // 接诊类型 - 来自 reception 表
            [
                'page'             => 'ReportConsultantOrder',
                'name'             => '接诊类型',
                'table'            => 'reception',
                'field'            => 'type',
                'field_alias'      => 'reception_type',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'api'              => '/cache/reception-type',
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                        'multiple'  => true,
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 成交状态 - 来自 reception_order 表
            [
                'page'             => 'ReportConsultantOrder',
                'name'             => '成交状态',
                'table'            => 'reception_order',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'  => true,
                        'clearable' => true,
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.reception_order.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 咨询科室 - 来自 reception 表
            [
                'page'             => 'ReportConsultantOrder',
                'name'             => '咨询科室',
                'table'            => 'reception',
                'field'            => 'department_id',
                'field_alias'      => 'reception_department_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/departments',
                'component_params' => json_encode([
                    'props' => [
                        'clearable'  => true,
                        'filterable' => true,
                        'multiple'   => true,
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 现场咨询 - 来自 reception 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '现场咨询',
                'table'      => 'reception',
                'field'      => 'consultant',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 咨询项目 - 来自 reception_items 关联表
            [
                'page'             => 'ReportConsultantOrder',
                'name'             => '咨询项目',
                'table'            => 'reception',
                'field'            => 'items',
                'field_type'       => 'varchar',
                'api'              => '/cache/items?cascader=1',
                'component'        => 'cascader',
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ]),
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
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "cy_reception.id IN (SELECT DISTINCT reception_id FROM cy_reception_items WHERE item_id IN (SELECT id FROM cy_item WHERE tree LIKE CONCAT((SELECT tree FROM cy_item WHERE id = ?), '-%') OR cy_item.id = ?))",
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
                                'sql'      => "cy_reception.id NOT IN (SELECT DISTINCT reception_id FROM cy_reception_items WHERE item_id IN (SELECT id FROM cy_item WHERE tree LIKE CONCAT((SELECT tree FROM cy_item WHERE id = ?), '-%') OR cy_item.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is null',
                        'wheres'   => [
                            [
                                'type' => 'whereRaw',
                                'sql'  => "cy_reception.id NOT IN (SELECT DISTINCT reception_id FROM cy_reception_items)",
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is not null',
                        'wheres'   => [
                            [
                                'type' => 'whereRaw',
                                'sql'  => "cy_reception.id IN (SELECT DISTINCT reception_id FROM cy_reception_items)",
                            ]
                        ]
                    ]
                ])
            ],
            // 媒介来源 - 来自 reception 表
            [
                'page'             => 'ReportConsultantOrder',
                'name'             => '媒介来源',
                'table'            => 'reception',
                'field'            => 'medium_id',
                'field_type'       => 'int',
                'api'              => '/cache/mediums?cascader=1',
                'component'        => 'cascader',
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ]),
                'component_params' => json_encode([
                    'props' => [
                        'props'      => [
                            'label'         => 'text',
                            'value'         => 'id',
                            'checkStrictly' => true
                        ],
                        'clearable'  => true,
                        'filterable' => true,
                    ]
                ]),
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_reception.medium_id IN (select id from cy_medium where tree LIKE CONCAT((SELECT tree FROM cy_medium WHERE id = ?), '-%') OR cy_medium.id = ?))",
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
                                'sql'      => "(cy_reception.medium_id NOT IN (select id from cy_medium where tree LIKE CONCAT((SELECT tree FROM cy_medium WHERE id = ?), '-%') OR cy_medium.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
            // 类别 - 来自 reception_order 表
            [
                'page'             => 'ReportConsultantOrder',
                'name'             => '类别',
                'table'            => 'reception_order',
                'field'            => 'type',
                'field_alias'      => 'reception_order_type',
                'field_type'       => 'varchar',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true,
                        'multiple'  => true,
                    ],
                    'options' => [
                        ['label' => '商品', 'value' => 'goods'],
                        ['label' => '项目', 'value' => 'product'],
                    ]
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 成交项目/商品名称 - 来自 reception_order 表
            [
                'page'         => 'ReportConsultantOrder',
                'name'         => '成交项目/商品名称',
                'table'        => 'reception_order',
                'field'        => 'product_name',
                'field_type'   => 'varchar',
                'component'    => 'input',
                'operators'    => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ]),
                'query_config' => json_encode([
                    [
                        'operator' => 'like',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_reception_order.product_name LIKE ? OR cy_reception_order.goods_name LIKE ?)",
                                'bindings' => [
                                    '%{$value}%',
                                    '%{$value}%'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => '=',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_reception_order.product_name = ? OR cy_reception_order.goods_name = ?)",
                                'bindings' => [
                                    '{$value}',
                                    '{$value}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => '<>',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_reception_order.product_name <> ? OR cy_reception_order.goods_name <> ?)",
                                'bindings' => [
                                    '{$value}',
                                    '{$value}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is null',
                        'wheres'   => [
                            [
                                'type' => 'whereRaw',
                                'sql'  => "(cy_reception_order.product_name IS NULL OR cy_reception_order.goods_name IS NULL)",
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is not null',
                        'wheres'   => [
                            [
                                'type' => 'whereRaw',
                                'sql'  => "(cy_reception_order.product_name IS NOT NULL OR cy_reception_order.goods_name IS NOT NULL)",
                            ]
                        ]
                    ]
                ])
            ],
            // 套餐名称 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '套餐名称',
                'table'      => 'reception_order',
                'field'      => 'package_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 次数/数量 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '次数/数量',
                'table'      => 'reception_order',
                'field'      => 'times',
                'field_type' => 'decimal',
                'component'  => 'input-number',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ])
            ],
            // 单位 - 来自 reception_order 表
            [
                'page'             => 'ReportConsultantOrder',
                'name'             => '单位',
                'table'            => 'reception_order',
                'field'            => 'unit_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/goods-units',
                'component_params' => json_encode([
                    'props' => [
                        'clearable'  => true,
                        'filterable' => true,
                        'multiple'   => true,
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 规格 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '规格',
                'table'      => 'reception_order',
                'field'      => 'specs',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 原价 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '原价',
                'table'      => 'reception_order',
                'field'      => 'price',
                'field_type' => 'decimal',
                'component'  => 'input-number',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ])
            ],
            // 执行价格 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '执行价格',
                'table'      => 'reception_order',
                'field'      => 'sales_price',
                'field_type' => 'decimal',
                'component'  => 'input-number',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ])
            ],
            // 成交价格 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '成交价格',
                'table'      => 'reception_order',
                'field'      => 'payable',
                'field_type' => 'decimal',
                'component'  => 'input-number',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ])
            ],
            // 支付金额 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '支付金额',
                'table'      => 'reception_order',
                'field'      => 'amount',
                'field_type' => 'decimal',
                'component'  => 'input-number',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ])
            ],
            // 券支付 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '券支付',
                'table'      => 'reception_order',
                'field'      => 'coupon',
                'field_type' => 'decimal',
                'component'  => 'input-number',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ])
            ],
            // 结算科室 - 来自 reception_order 表
            [
                'page'             => 'ReportConsultantOrder',
                'name'             => '结算科室',
                'table'            => 'reception_order',
                'field'            => 'department_id',
                'field_alias'      => 'reception_order_department_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/departments',
                'component_params' => json_encode([
                    'props' => [
                        'clearable'  => true,
                        'filterable' => true,
                        'multiple'   => true,
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 录单人员 - 来自 reception_order 表
            [
                'page'       => 'ReportConsultantOrder',
                'name'       => '录单人员',
                'table'      => 'reception_order',
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
