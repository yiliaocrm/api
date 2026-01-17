<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportCustomerProductSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'ReportCustomerProduct',
                'name'             => '收费日期',
                'table'            => 'customer_product',
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
                'page'             => 'ReportCustomerProduct',
                'name'             => '项目状态',
                'table'            => 'customer_product',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.customer_product.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'ReportCustomerProduct',
                'name'             => '接诊类型',
                'table'            => 'customer_product',
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
                'page'       => 'ReportCustomerProduct',
                'name'       => '销售顾问',
                'table'      => 'customer_product',
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
            [
                'page'       => 'ReportCustomerProduct',
                'name'       => '二开人员',
                'table'      => 'customer_product',
                'field'      => 'ek_user',
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
                'page'       => 'ReportCustomerProduct',
                'name'       => '助诊医生',
                'table'      => 'customer_product',
                'field'      => 'doctor',
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
                'page'       => 'ReportCustomerProduct',
                'name'       => '项目名称',
                'table'      => 'customer_product',
                'field'      => 'product_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'             => 'ReportCustomerProduct',
                'name'             => '项目分类',
                'table'            => 'customer_product',
                'field'            => 'product_type_id',
                'api'              => '/cache/product-type?cascader=1',
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
                        'joins'    => [
                            [
                                'table'    => 'product',
                                'first'    => 'product.id',
                                'operator' => '=',
                                'second'   => 'customer_product.product_id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_product.type_id IN (SELECT id FROM cy_product_type WHERE tree LIKE CONCAT((SELECT tree FROM cy_product_type WHERE id = ?), '-%') OR cy_product_type.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => '<>',
                        'joins'    => [
                            [
                                'table'    => 'product',
                                'first'    => 'product.id',
                                'operator' => '=',
                                'second'   => 'customer_product.product_id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_product.type_id NOT IN (SELECT id FROM cy_product_type WHERE tree LIKE CONCAT((SELECT tree FROM cy_product_type WHERE id = ?), '-%') OR cy_product_type.id = ?))",
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
                'page'       => 'ReportCustomerProduct',
                'name'       => '套餐名称',
                'table'      => 'customer_product',
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
            [
                'page'             => 'ReportCustomerProduct',
                'name'             => '套餐分类',
                'table'            => 'customer_product',
                'field'            => 'product_package_type_id',
                'api'              => '/cache/product-package-type?cascader=1',
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
                        'joins'    => [
                            [
                                'table'    => 'product_package',
                                'first'    => 'product_package.id',
                                'operator' => '=',
                                'second'   => 'customer_product.package_id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_product_package.type_id IN (SELECT id FROM cy_product_package_type WHERE tree LIKE CONCAT((SELECT tree FROM cy_product_package_type WHERE id = ?), '-%') OR cy_product_package_type.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => '<>',
                        'joins'    => [
                            [
                                'table'    => 'product_package',
                                'first'    => 'product_package.id',
                                'operator' => '=',
                                'second'   => 'customer_product.product_id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_product_package.type_id NOT IN (SELECT id FROM cy_product_package_type WHERE tree LIKE CONCAT((SELECT tree FROM cy_product_package_type WHERE id = ?), '-%') OR cy_product_package_type.id = ?))",
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
                'page'             => 'ReportCustomerProduct',
                'name'             => '结算科室',
                'table'            => 'customer_product',
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
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'ReportCustomerProduct',
                'name'             => '媒介来源',
                'table'            => 'customer_product',
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
                                'sql'      => "(cy_customer_product.medium_id IN (select id from cy_medium where tree LIKE CONCAT((SELECT tree FROM cy_medium WHERE id = ?), '-%') OR cy_medium.id = ?))",
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
                                'sql'      => "(cy_customer_product.medium_id NOT IN (select id from cy_medium where tree LIKE CONCAT((SELECT tree FROM cy_medium WHERE id = ?), '-%') OR cy_medium.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
        ];
    }
}
