<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerGroupFieldsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $fields = [
            [
                'table'      => 'customer',
                'field'      => 'name',
                'field_type' => 'varchar',
                'table_name' => '顾客信息',
                'field_name' => '顾客姓名',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'idcard',
                'field_type' => 'varchar',
                'table_name' => '顾客信息',
                'field_name' => '顾客卡号',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'file_number',
                'field_type' => 'varchar',
                'table_name' => '顾客信息',
                'field_name' => '档案编号',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'qq',
                'field_type' => 'varchar',
                'table_name' => '顾客信息',
                'field_name' => '联系QQ',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'wechat',
                'field_type' => 'varchar',
                'table_name' => '顾客信息',
                'field_name' => '微信号码',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'sfz',
                'field_type' => 'varchar',
                'table_name' => '顾客信息',
                'field_name' => '身份证号',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'job_id',
                'field_type' => 'int',
                'table_name' => '顾客信息',
                'field_name' => '职业信息',
                'api'        => '/cache/customer-job',
                'component'  => 'select',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'economic_id',
                'field_type' => 'int',
                'table_name' => '顾客信息',
                'field_name' => '经济能力',
                'api'        => '/cache/customer-economic',
                'component'  => 'select',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'marital',
                'field_type'       => 'int',
                'table_name'       => '顾客信息',
                'field_name'       => '婚姻状况',
                'component'        => 'select',
                'component_params' => json_encode([
                    'options' => [
                        ['label' => '未知', 'value' => 1],
                        ['label' => '未婚', 'value' => 2],
                        ['label' => '已婚', 'value' => 3],
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'sex',
                'field_type'       => 'tinyint',
                'table_name'       => '顾客信息',
                'field_name'       => '顾客性别',
                'component'        => 'select',
                'component_params' => json_encode([
                    'options' => [
                        ['label' => '男', 'value' => 1],
                        ['label' => '女', 'value' => 2],
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'birthday',
                'field_type'       => 'date',
                'table_name'       => '顾客信息',
                'field_name'       => '顾客生日',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'age',
                'field_type'       => 'int',
                'table_name'       => '顾客信息',
                'field_name'       => '顾客年龄',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 200
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'        => 'customer',
                'field'        => 'phone',
                'field_type'   => 'varchar',
                'table_name'   => '顾客信息',
                'field_name'   => '联系电话',
                'component'    => 'input',
                'operators'    => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
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
                    [
                        'operator' => 'is null',
                        'wheres'   => [
                            [
                                'type' => 'whereRaw',
                                'sql'  => 'NOT EXISTS (SELECT 1 FROM cy_customer_phones WHERE cy_customer_phones.customer_id = cy_customer.id)',
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is not null',
                        'wheres'   => [
                            [
                                'type' => 'whereRaw',
                                'sql'  => 'EXISTS (SELECT 1 FROM cy_customer_phones WHERE cy_customer_phones.customer_id = cy_customer.id)',
                            ]
                        ]
                    ]
                ]),
            ],
            [
                'table'            => 'customer',
                'field'            => 'address_id',
                'field_type'       => 'int',
                'table_name'       => '顾客信息',
                'field_name'       => '通讯地址',
                'api'              => '/cache/address?cascader=1',
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
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_customer.address_id IN (select id from cy_address where tree LIKE CONCAT((SELECT tree FROM cy_address WHERE id = ?), '-%') OR cy_address.id = ?))",
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
                                'sql'      => "(cy_customer.address_id NOT IN (select id from cy_address where tree LIKE CONCAT((SELECT tree FROM cy_address WHERE id = ?), '-%') OR cy_address.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'medium_id',
                'field_type'       => 'int',
                'table_name'       => '顾客信息',
                'field_name'       => '首次来源',
                'api'              => '/cache/mediums?cascader=1',
                'component'        => 'cascader',
                'component_params' => json_encode([
                    'props' => [
                        'props'      => [
                            'label'         => 'name',
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
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'referrer_user_id',
                'field_type' => 'int',
                'table_name' => '顾客信息',
                'field_name' => '推荐员工',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'referrer_customer_id',
                'field_type' => 'varchar',
                'table_name' => '顾客信息',
                'field_name' => '推荐客户',
                'component'  => 'customer',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'tags',
                'field_type'       => 'varchar',
                'table_name'       => '顾客信息',
                'field_name'       => '顾客标签',
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
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
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
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'consultant',
                'field_type' => 'int',
                'table_name' => '顾客信息',
                'field_name' => '归属咨询',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'ascription',
                'field_type' => 'int',
                'table_name' => '顾客信息',
                'field_name' => '归属开发',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'service_id',
                'field_type' => 'int',
                'table_name' => '顾客信息',
                'field_name' => '专属客服',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'doctor_id',
                'field_type' => 'int',
                'table_name' => '顾客信息',
                'field_name' => '主治医生',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'items',
                'field_type'       => 'varchar',
                'table_name'       => '顾客信息',
                'field_name'       => '咨询项目',
                'api'              => '/cache/items?cascader=1',
                'component'        => 'cascader',
                'component_params' => json_encode([
                    'props' => [
                        'props'      => [
                            'label'         => 'name',
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
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'total_payment',
                'field_type'       => 'decimal',
                'table_name'       => '顾客信息',
                'field_name'       => '累计付款',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'amount',
                'field_type'       => 'decimal',
                'table_name'       => '顾客信息',
                'field_name'       => '累计消费',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'balance',
                'field_type'       => 'decimal',
                'table_name'       => '顾客信息',
                'field_name'       => '账户余额',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'integral',
                'field_type'       => 'int',
                'table_name'       => '顾客信息',
                'field_name'       => '现有积分',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'last_followup',
                'field_type'       => 'timestamp',
                'table_name'       => '顾客信息',
                'field_name'       => '最近回访',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'last_time',
                'field_type'       => 'timestamp',
                'table_name'       => '顾客信息',
                'field_name'       => '最近上门',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'last_treatment',
                'field_type'       => 'timestamp',
                'table_name'       => '顾客信息',
                'field_name'       => '最近治疗',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'first_time',
                'field_type'       => 'timestamp',
                'table_name'       => '顾客信息',
                'field_name'       => '初诊时间',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'created_at',
                'field_type'       => 'timestamp',
                'table_name'       => '顾客信息',
                'field_name'       => '建档时间',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between']
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'user_id',
                'field_type' => 'int',
                'table_name' => '顾客信息',
                'field_name' => '建档人员',
                'api'        => null,
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'table'      => 'customer',
                'field'      => 'remark',
                'field_type' => 'text',
                'table_name' => '顾客信息',
                'field_name' => '备注信息',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                ])
            ],
            [
                'table'            => 'customer',
                'field'            => 'customer_group_id',
                'field_type'       => 'int',
                'table_name'       => '顾客信息',
                'field_name'       => '所在分群',
                'api'              => '/cache/customer-group?cascader=true',
                'component'        => 'cascader',
                'component_params' => json_encode([
                    'props' => [
                        'clearable'  => true,
                        'filterable' => true
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>']
                ]),
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "`cy_customer`.`id` IN (SELECT `customer_id` FROM `cy_customer_group_details` WHERE `customer_group_id` = ?)",
                                'bindings' => [
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
                                'sql'      => "`cy_customer`.`id` NOT IN (SELECT `customer_id` FROM `cy_customer_group_details` WHERE `customer_group_id` = ?)",
                                'bindings' => [
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'product_type_id',
                'field_type'       => 'int',
                'table_name'       => '已购项目',
                'field_name'       => '项目分类',
                'api'              => '/cache/product-type?cascader=1',
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
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
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
                                'type'     => 'left'
                            ],
                            [
                                'table'    => 'product_type',
                                'first'    => 'product_type.id',
                                'operator' => '=',
                                'second'   => 'product.type_id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_product_type.tree LIKE CONCAT((SELECT tree FROM cy_product_type WHERE id = ?), '-%') OR cy_product_type.id = ?)",
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
                                'type'     => 'left'
                            ],
                            [
                                'table'    => 'product_type',
                                'first'    => 'product_type.id',
                                'operator' => '=',
                                'second'   => 'product.type_id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "cy_product_type.tree NOT LIKE CONCAT((SELECT tree FROM cy_product_type WHERE id = ?), '-%') AND cy_product_type.id <> ?",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}'
                                ]
                            ]
                        ]
                    ],
                ])
            ],
            [
                'table'      => 'customer_product',
                'field'      => 'product_name',
                'field_type' => 'varchar',
                'table_name' => '已购项目',
                'field_name' => '项目名称',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                ])
            ],
            [
                'table'      => 'customer_product',
                'field'      => 'package_name',
                'field_type' => 'varchar',
                'table_name' => '已购项目',
                'field_name' => '套餐名称',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'table_name'       => '已购项目',
                'field_name'       => '项目状态',
                'component'        => 'select',
                'component_params' => json_encode([
                    'options' => [
                        ['label' => '等待划扣', 'value' => 1],
                        ['label' => '完成治疗', 'value' => 2],
                        ['label' => '项目退费', 'value' => 3],
                        ['label' => '项目过期', 'value' => 4],
                        ['label' => '疗程中', 'value' => 5],
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'expire_time',
                'field_type'       => 'date',
                'table_name'       => '已购项目',
                'field_name'       => '过期时间',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'times',
                'field_type'       => 'int',
                'table_name'       => '已购项目',
                'field_name'       => '项目次数',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'used',
                'field_type'       => 'int',
                'table_name'       => '已购项目',
                'field_name'       => '已用次数',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'leftover',
                'field_type'       => 'int',
                'table_name'       => '已购项目',
                'field_name'       => '剩余次数',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'refund_times',
                'field_type'       => 'int',
                'table_name'       => '已购项目',
                'field_name'       => '退款次数',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'invoice_amount',
                'field_type'       => 'decimal',
                'table_name'       => '已购项目',
                'field_name'       => '开票金额',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'price',
                'field_type'       => 'decimal',
                'table_name'       => '已购项目',
                'field_name'       => '项目原价',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'sales_price',
                'field_type'       => 'decimal',
                'table_name'       => '已购项目',
                'field_name'       => '执行价格',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'payable',
                'field_type'       => 'decimal',
                'table_name'       => '已购项目',
                'field_name'       => '应收金额',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'income',
                'field_type'       => 'decimal',
                'table_name'       => '已购项目',
                'field_name'       => '实收金额',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'deposit',
                'field_type'       => 'decimal',
                'table_name'       => '已购项目',
                'field_name'       => '余额支付',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'coupon',
                'field_type'       => 'decimal',
                'table_name'       => '已购项目',
                'field_name'       => '卷额支付',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'customer_product',
                'field'            => 'arrearage',
                'field_type'       => 'decimal',
                'table_name'       => '已购项目',
                'field_name'       => '欠款金额',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'reservation',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'table_name'       => '网电报单',
                'field_name'       => '是否上门',
                'component'        => 'select',
                'component_params' => json_encode([
                    'options' => [
                        ['label' => '未上门', 'value' => 1],
                        ['label' => '已来院', 'value' => 2],
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '=']
                ])
            ],
            [
                'table'      => 'reservation',
                'field'      => 'type',
                'field_type' => 'int',
                'table_name' => '网电报单',
                'field_name' => '受理类型',
                'api'        => '/cache/reservation-type',
                'component'  => 'select',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'            => 'reservation',
                'field'            => 'items',
                'field_type'       => 'varchar',
                'table_name'       => '网电报单',
                'field_name'       => '咨询项目',
                'api'              => '/cache/items?cascader=1',
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
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'joins'    => [
                            [
                                'table'    => 'reservation_items',
                                'first'    => 'reservation_items.reservation_id',
                                'operator' => '=',
                                'second'   => 'reservation.id',
                                'type'     => 'left'
                            ],
                            [
                                'table'    => 'item',
                                'first'    => 'item.id',
                                'operator' => '=',
                                'second'   => 'reservation_items.item_id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "(cy_item.tree LIKE CONCAT((SELECT tree FROM cy_item WHERE id = ?), '-%') OR cy_item.id = ?)",
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
                                'table'    => 'reservation_items',
                                'first'    => 'reservation_items.reservation_id',
                                'operator' => '=',
                                'second'   => 'reservation.id',
                                'type'     => 'inner'
                            ],
                            [
                                'table'    => 'item',
                                'first'    => 'item.id',
                                'operator' => '=',
                                'second'   => 'reservation_items.item_id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "cy_item.tree NOT LIKE CONCAT((SELECT tree FROM cy_item WHERE id = ?), '-%') AND cy_item.id <> ?",
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
                                'table'    => 'reservation_items',
                                'first'    => 'reservation_items.customer_id',
                                'operator' => '=',
                                'second'   => 'reservation.id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'   => 'whereNull',
                                'column' => 'reservation_items.item_id'
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is not null',
                        'joins'    => [
                            [
                                'table'    => 'reservation_items',
                                'first'    => 'reservation_items.customer_id',
                                'operator' => '=',
                                'second'   => 'reservation.id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'   => 'whereNotNull',
                                'column' => 'reservation_items.item_id'
                            ]
                        ]
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'reservation',
                'field'            => 'date',
                'field_type'       => 'date',
                'table_name'       => '网电报单',
                'field_name'       => '受理日期',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'      => 'reservation',
                'field'      => 'department_id',
                'field_type' => 'int',
                'table_name' => '网电报单',
                'field_name' => '咨询科室',
                'component'  => 'select',
                'api'        => '/cache/departments',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reservation',
                'field'      => 'ascription',
                'field_type' => 'int',
                'table_name' => '网电报单',
                'field_name' => '咨询人员',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reservation',
                'field'      => 'user_id',
                'field_type' => 'int',
                'table_name' => '网电报单',
                'field_name' => '录单人员',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'            => 'reservation',
                'field'            => 'cometime',
                'field_type'       => 'datetime',
                'table_name'       => '网电报单',
                'field_name'       => '上门时间',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                    ['label' => '为空', 'value' => 'is null'],
                    ['label' => '不为空', 'value' => 'is not null'],
                ])
            ],
            [
                'table'            => 'reservation',
                'field'            => 'created_at',
                'field_type'       => 'timestamp',
                'table_name'       => '网电报单',
                'field_name'       => '录单日期',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                ])
            ],
            [
                'table'      => 'reception',
                'field'      => 'department_id',
                'field_type' => 'int',
                'table_name' => '现场设计',
                'field_name' => '咨询科室',
                'component'  => 'select',
                'api'        => '/cache/departments',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reception',
                'field'      => 'type',
                'field_type' => 'tinyint',
                'table_name' => '现场设计',
                'field_name' => '接诊类型',
                'component'  => 'select',
                'api'        => '/cache/reception-type',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'            => 'reception',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'table_name'       => '现场设计',
                'field_name'       => '成交状态',
                'component'        => 'select',
                'component_params' => json_encode([
                    'options' => [
                        ['label' => '未成交', 'value' => 1],
                        ['label' => '已成交', 'value' => 2],
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reception',
                'field'      => 'consultant',
                'field_type' => 'int',
                'table_name' => '现场设计',
                'field_name' => '现场咨询',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reception',
                'field'      => 'reception',
                'field_type' => 'int',
                'table_name' => '现场设计',
                'field_name' => '接待人员',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reception',
                'field'      => 'user_id',
                'field_type' => 'int',
                'table_name' => '现场设计',
                'field_name' => '录单人员',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reception',
                'field'      => 'ek_user',
                'field_type' => 'int',
                'table_name' => '现场设计',
                'field_name' => '二开人员',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reception',
                'field'      => 'doctor',
                'field_type' => 'int',
                'table_name' => '现场设计',
                'field_name' => '接诊医生',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'      => 'reception',
                'field'      => 'remark',
                'field_type' => 'text',
                'table_name' => '现场设计',
                'field_name' => '备注信息',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'table'            => 'reception',
                'field'            => 'created_at',
                'field_type'       => 'timestamp',
                'table_name'       => '现场设计',
                'field_name'       => '录单日期',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between']
                ])
            ],
            [
                'table'      => 'treatment',
                'field'      => 'product_name',
                'field_type' => 'varchar',
                'table_name' => '治疗记录',
                'field_name' => '项目名称',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'table'      => 'treatment',
                'field'      => 'package_name',
                'field_type' => 'varchar',
                'table_name' => '治疗记录',
                'field_name' => '套餐名称',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                    ['label' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'table'      => 'treatment',
                'field'      => 'department_id',
                'field_type' => 'int',
                'table_name' => '治疗记录',
                'field_name' => '执行科室',
                'component'  => 'select',
                'api'        => '/cache/departments',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'            => 'treatment',
                'field'            => 'times',
                'field_type'       => 'int',
                'table_name'       => '治疗记录',
                'field_name'       => '划扣次数',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'treatment',
                'field'            => 'price',
                'field_type'       => 'decimal',
                'table_name'       => '治疗记录',
                'field_name'       => '划扣价格',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'            => 'treatment',
                'field'            => 'arrearage',
                'field_type'       => 'decimal',
                'table_name'       => '治疗记录',
                'field_name'       => '欠款金额',
                'component'        => 'input-number',
                'component_params' => json_encode([
                    'props' => [
                        'min' => 0,
                        'max' => 9999999
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                ])
            ],
            [
                'table'      => 'treatment',
                'field'      => 'user_id',
                'field_type' => 'int',
                'table_name' => '治疗记录',
                'field_name' => '划扣人员',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '包含', 'value' => 'in'],
                    ['label' => '不包含', 'value' => 'not in'],
                ])
            ],
            [
                'table'        => 'treatment',
                'field'        => 'participants',
                'field_type'   => 'int',
                'table_name'   => '治疗记录',
                'field_name'   => '配台人员',
                'component'    => 'user',
                'operators'    => json_encode([
                    ['label' => '等于', 'value' => '='],
                ]),
                'query_config' => json_encode([
                    [
                        'operator' => '=',
                        'joins'    => [
                            [
                                'table'    => 'treatment_participants',
                                'first'    => 'treatment_participants.treatment_id',
                                'operator' => '=',
                                'second'   => 'treatment.id',
                                'type'     => 'left'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'where',
                                'column'   => 'treatment_participants.user_id',
                                'operator' => '=',
                            ]
                        ]
                    ],
                ])
            ],
            [
                'table'            => 'treatment',
                'field'            => 'created_at',
                'field_type'       => 'timestamp',
                'table_name'       => '治疗记录',
                'field_name'       => '划扣时间',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between']
                ])
            ],
        ];

        // 添加搜索字段
        foreach ($fields as &$field) {
            $field['api']              = $field['api'] ?? null;
            $field['keyword']          = implode(',', parse_pinyin($field['table_name'] . $field['field_name']));
            $field['auto_join']        = $field['auto_join'] ?? 1;
            $field['query_config']     = $field['query_config'] ?? null;
            $field['component_params'] = $field['component_params'] ?? null;
        }

        DB::table('customer_group_fields')->truncate();
        DB::table('customer_group_fields')->insert($fields);
    }
}
