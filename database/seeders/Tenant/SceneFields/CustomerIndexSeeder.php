<?php

namespace Database\Seeders\Tenant\SceneFields;

class CustomerIndexSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'CustomerIndex',
                'name'             => '建档时间',
                'table'            => 'customer',
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
                'page'             => 'CustomerIndex',
                'name'             => '初诊日期',
                'table'            => 'customer',
                'field'            => 'first_time',
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
                    ['text' => '区间', 'value' => 'between'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'             => 'CustomerIndex',
                'name'             => '最近上门',
                'table'            => 'customer',
                'field'            => 'last_time',
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
                    ['text' => '区间', 'value' => 'between'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'             => 'CustomerIndex',
                'name'             => '最近回访',
                'table'            => 'customer',
                'field'            => 'last_followup',
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
                    ['text' => '区间', 'value' => 'between'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'CustomerIndex',
                'name'       => '归属顾问',
                'table'      => 'customer',
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
                'page'       => 'CustomerIndex',
                'name'       => '归属开发',
                'table'      => 'customer',
                'field'      => 'ascription',
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
                'page'       => 'CustomerIndex',
                'name'       => '专属客服',
                'table'      => 'customer',
                'field'      => 'service_id',
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
                'page'       => 'CustomerIndex',
                'name'       => '主治医生',
                'table'      => 'customer',
                'field'      => 'doctor_id',
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
                'page'       => 'CustomerIndex',
                'name'       => '顾客姓名',
                'table'      => 'customer',
                'field'      => 'name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '包含', 'value' => 'like'],
                ])
            ],
            [
                'page'       => 'CustomerIndex',
                'name'       => '顾客卡号',
                'table'      => 'customer',
                'field'      => 'idcard',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '包含', 'value' => 'like'],
                ])
            ],
            [
                'page'       => 'CustomerIndex',
                'name'       => '档案编号',
                'table'      => 'customer',
                'field'      => 'file_number',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'CustomerIndex',
                'name'       => '联系QQ',
                'table'      => 'customer',
                'field'      => 'qq',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'CustomerIndex',
                'name'       => '累计付款',
                'table'      => 'customer',
                'field'      => 'total_payment',
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
                'page'       => 'CustomerIndex',
                'name'       => '累计消费',
                'table'      => 'customer',
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
                'page'       => 'CustomerIndex',
                'name'       => '账户余额',
                'table'      => 'customer',
                'field'      => 'balance',
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
                'page'             => 'CustomerIndex',
                'name'             => '首次来源',
                'table'            => 'customer',
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
                                'sql'      => "(cy_customer.medium_id IN (select id from cy_medium where tree LIKE CONCAT((SELECT tree FROM cy_medium WHERE id = ?), '-%') OR cy_medium.id = ?))",
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
                                'sql'      => "(cy_customer.medium_id NOT IN (select id from cy_medium where tree LIKE CONCAT((SELECT tree FROM cy_medium WHERE id = ?), '-%') OR cy_medium.id = ?))",
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
                'page'       => 'CustomerIndex',
                'name'       => '推荐员工',
                'table'      => 'customer',
                'field'      => 'referrer_user_id',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '!='],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ]),
            ],
            [
                'page'       => 'CustomerIndex',
                'name'       => '推荐客户',
                'table'      => 'customer',
                'field'      => 'referrer_customer_id',
                'field_type' => 'varchar',
                'component'  => 'customer',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '!='],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ]),
            ],
            [
                'page'             => 'CustomerIndex',
                'name'             => '顾客标签',
                'table'            => 'customer',
                'field'            => 'tags',
                'field_type'       => 'varchar',
                'api'              => '/cache/tags?cascader=1',
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
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ]),
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'joins'    => [
                            [
                                'table'    => 'customer_tags',
                                'first'    => 'customer_tags.customer_id',
                                'operator' => '=',
                                'second'   => 'customer.id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "cy_customer_tags.tags_id IN (SELECT id FROM cy_tags WHERE tree LIKE CONCAT((SELECT tree FROM cy_tags WHERE id = ?), '-%') OR cy_tags.id = ?)",
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
                                'table'    => 'customer_tags',
                                'first'    => 'customer_tags.customer_id',
                                'operator' => '=',
                                'second'   => 'customer.id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "cy_customer_tags.tags_id Not IN (SELECT id FROM cy_tags WHERE tree LIKE CONCAT((SELECT tree FROM cy_tags WHERE id = ?), '-%') AND cy_tags.id <> ?)",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is null',
                        'joins'    => [
                            [
                                'table'    => 'customer_tags',
                                'first'    => 'customer_tags.customer_id',
                                'operator' => '=',
                                'second'   => 'customer.id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'   => 'whereNull',
                                'column' => 'customer_tags.tags_id'
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is not null',
                        'joins'    => [
                            [
                                'table'    => 'customer_tags',
                                'first'    => 'customer_tags.customer_id',
                                'operator' => '=',
                                'second'   => 'customer.id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'   => 'whereNotNull',
                                'column' => 'customer_tags.tags_id'
                            ]
                        ]
                    ]
                ])
            ],
            [
                'page'             => 'CustomerIndex',
                'name'             => '咨询项目',
                'table'            => 'customer',
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
                                'sql'      => "cy_customer.id IN (SELECT DISTINCT customer_id FROM cy_customer_items WHERE item_id IN (SELECT id FROM cy_item WHERE tree LIKE CONCAT((SELECT tree FROM cy_item WHERE id = ?), '-%') OR cy_item.id = ?))",
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
                                'sql'      => "cy_customer.id NOT IN (SELECT DISTINCT customer_id FROM cy_customer_items WHERE item_id IN (SELECT id FROM cy_item WHERE tree LIKE CONCAT((SELECT tree FROM cy_item WHERE id = ?), '-%') OR cy_item.id = ?))",
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
                                'sql'  => "cy_customer.id NOT IN (SELECT DISTINCT customer_id FROM cy_customer_items)",
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is not null',
                        'wheres'   => [
                            [
                                'type' => 'whereRaw',
                                'sql'  => "cy_customer.id IN (SELECT DISTINCT customer_id FROM cy_customer_items)",
                            ]
                        ]
                    ]
                ])
            ],
            [
                'page'         => 'CustomerIndex',
                'name'         => '联系电话',
                'table'        => 'customer',
                'field'        => 'phone',
                'field_type'   => 'varchar',
                'component'    => 'input',
                'operators'    => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
                'query_config' => json_encode([
                    [
                        'operator' => '=',
                        'joins'    => [
                            [
                                'table'    => 'customer_phones',
                                'first'    => 'customer_phones.customer_id',
                                'operator' => '=',
                                'second'   => 'customer.id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'where',
                                'column'   => 'customer_phones.phone',
                                'operator' => '=',
                            ]
                        ]
                    ],
                    [
                        'operator' => '<>',
                        'joins'    => [
                            [
                                'table'    => 'customer_phones',
                                'first'    => 'customer_phones.customer_id',
                                'operator' => '=',
                                'second'   => 'customer.id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'where',
                                'column'   => 'customer_phones.phone',
                                'operator' => '<>'
                            ]
                        ]
                    ],
                    [
                        'operator' => 'like',
                        'joins'    => [
                            [
                                'table'    => 'customer_phones',
                                'first'    => 'customer_phones.customer_id',
                                'operator' => '=',
                                'second'   => 'customer.id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'where',
                                'column'   => 'customer_phones.phone',
                                'operator' => 'like'
                            ]
                        ]
                    ],
                ])
            ],
            [
                'page'       => 'CustomerIndex',
                'name'       => '顾客备注',
                'table'      => 'customer',
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
