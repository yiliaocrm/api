<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportErkaiDetailSeeder extends BaseSceneFieldSeeder
{
    /**
     * 获取二开零购明细表页面搜索字段配置
     */
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'ReportErkaiDetail',
                'name'             => '媒介来源',
                'table'            => 'erkai',
                'field'            => 'medium_id',
                'field_type'       => 'int',
                'component'        => 'cascader',
                'api'              => '/cache/mediums?cascader=1',
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
                                'sql'      => "(cy_erkai.medium_id IN (select id from cy_medium where tree LIKE CONCAT((SELECT tree FROM cy_medium WHERE id = ?), '-%') OR cy_medium.id = ?))",
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
                                'sql'      => "(cy_erkai.medium_id NOT IN (select id from cy_medium where tree LIKE CONCAT((SELECT tree FROM cy_medium WHERE id = ?), '-%') OR cy_medium.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
            [
                'page'         => 'ReportErkaiDetail',
                'name'         => '项目/商品名称',
                'table'        => 'erkai_detail',
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
                                'sql'      => "(cy_erkai_detail.product_name LIKE ? OR cy_erkai_detail.goods_name LIKE ?)",
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
                                'sql'      => "(cy_erkai_detail.product_name NOT LIKE ? OR cy_erkai_detail.goods_name NOT LIKE ?)",
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
                'page'       => 'ReportErkaiDetail',
                'name'       => '原价',
                'table'      => 'erkai_detail',
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
            [
                'page'       => 'ReportErkaiDetail',
                'name'       => '成交价格',
                'table'      => 'erkai_detail',
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
            [
                'page'       => 'ReportErkaiDetail',
                'name'       => '支付金额',
                'table'      => 'erkai_detail',
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
            [
                'page'       => 'ReportErkaiDetail',
                'name'       => '券支付',
                'table'      => 'erkai_detail',
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
            [
                'page'             => 'ReportErkaiDetail',
                'name'             => '结算科室',
                'table'            => 'erkai_detail',
                'field'            => 'department_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/departments',
                'component_params' => json_encode([
                    'props' => [
                        'clearable'  => true,
                        'filterable' => true,
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'       => 'ReportErkaiDetail',
                'name'       => '录单人员',
                'table'      => 'erkai_detail',
                'field'      => 'user_id',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null'],
                ])
            ],
        ];
    }
}
