<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportPerformanceSalesSeeder extends BaseSceneFieldSeeder
{
    /**
     * 获取员工业绩报表页面搜索字段配置
     */
    public function getConfig(): array
    {
        return [
            [
                'page'       => 'ReportPerformanceSales',
                'name'       => '计提人员',
                'table'      => 'sales_performance',
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
                'page'             => 'ReportPerformanceSales',
                'name'             => '项目名称',
                'table'            => 'sales_performance',
                'field'            => 'product_name',
                'field_type'       => 'varchar',
                'component'        => 'input',
                'operators'        => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
                'component_params' => json_encode([
                    'props' => [
                        'placeholder' => '请输入项目\物品名称',
                    ],
                ]),
                'query_config'     => json_encode([
                    [
                        'operator' => 'like',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_sales_performance.product_name LIKE ? OR cy_sales_performance.goods_name LIKE ?)",
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
                                'sql'      => "(cy_sales_performance.product_name = ? OR cy_sales_performance.goods_name = ?)",
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
                                'sql'      => "(cy_sales_performance.product_name <> ? OR cy_sales_performance.goods_name <> ?)",
                                'bindings' => [
                                    '{$value}',
                                    '{$value}'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
            [
                'page'             => 'ReportPerformanceSales',
                'name'             => '业务类型',
                'table'            => 'sales_performance',
                'field'            => 'table_name',
                'field_type'       => 'varchar',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.sales_performance.table_name')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'ReportPerformanceSales',
                'name'             => '计提职位',
                'table'            => 'sales_performance',
                'field'            => 'position',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.sales_performance.position')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'ReportPerformanceSales',
                'name'             => '接诊类型',
                'table'            => 'sales_performance',
                'field'            => 'reception_type',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'api'              => '/cache/reception-type',
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
                'page'             => 'ReportPerformanceSales',
                'name'             => '职员科室',
                'table'            => 'sales_performance',
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
                    ['text' => '等于', 'value' => '='],
                ]),
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'joins'    => [
                            [
                                'table'    => 'users',
                                'first'    => 'users.id',
                                'operator' => '=',
                                'second'   => 'sales_performance.user_id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "cy_users.department_id = ?",
                                'bindings' => [
                                    '{$value}'
                                ]
                            ]
                        ]
                    ],
                ])
            ]
        ];
    }
}
