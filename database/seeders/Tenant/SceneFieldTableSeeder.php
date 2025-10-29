<?php

namespace Database\Seeders\Tenant;

use App\Enums\AppointmentType;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SceneFieldTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $fields = array_merge(
            $this->getCustomerIndexConfig(),
            $this->getPurchaseIndexConfig(),
            $this->getWorkbenchAppointmentConfig(),
            $this->getReportCustomerProductConfig(),
            $this->getReportCustomerGoodsConfig(),
            $this->getReportCustomerRefundConfig(),
            $this->getReportPerformanceSalesConfig(),
            $this->getReportPurchaseDetailConfig(),
            $this->getReportDepartmentPickingDetail(),
            $this->getReportConsumableDetail(),
            $this->getPurchaseReturnIndexConfig(),
            $this->getDepartmentPickingConfig(),
            $this->getInventoryIndexConfig(),
            $this->getInventoryBatchsIndexConfig(),
            $this->getInventoryTransferIndexConfig(),
            $this->getInventoryLossIndexConfig(),
            $this->getInventoryOverflowIndexConfig(),
        );

        // 添加搜索字段
        foreach ($fields as &$field) {
            $field['api']              = $field['api'] ?? null;
            $field['keyword']          = implode(',', parse_pinyin($field['name']));
            $field['query_config']     = $field['query_config'] ?? null;
            $field['component_params'] = $field['component_params'] ?? null;
        }

        DB::table('scene_fields')->truncate();
        DB::table('scene_fields')->insert($fields);
    }

    /**
     * 转换设置配置为选项
     * @param string $key
     * @return array
     */
    protected function convertSettingConfigToOptions(string $key): array
    {
        $config  = config($key);
        $options = [];
        foreach ($config as $value => $label) {
            $options[] = ['label' => $label, 'value' => $value];
        }
        return $options;
    }

    /**
     * 顾客管理页面搜索字段配置
     * @return array
     */
    private function getCustomerIndexConfig(): array
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

    /**
     * 获取采购入库页面搜索字段配置
     * @return array
     */
    private function getPurchaseIndexConfig(): array
    {
        return [
            [
                'page'       => 'PurchaseIndex',
                'name'       => '单据号',
                'table'      => 'purchase',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'PurchaseIndex',
                'name'             => '单据日期',
                'table'            => 'purchase',
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
                'page'             => 'PurchaseIndex',
                'name'             => '录单日期',
                'table'            => 'purchase',
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
                'page'             => 'PurchaseIndex',
                'name'             => '审核日期',
                'table'            => 'purchase',
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
                'page'             => 'PurchaseIndex',
                'name'             => '单据状态',
                'table'            => 'purchase',
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
                'page'             => 'PurchaseIndex',
                'name'             => '进货仓库',
                'table'            => 'purchase',
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
                'page'       => 'PurchaseIndex',
                'name'       => '经办人员',
                'table'      => 'purchase',
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
                'page'       => 'PurchaseIndex',
                'name'       => '录单人员',
                'table'      => 'purchase',
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
                'page'       => 'PurchaseIndex',
                'name'       => '审核人员',
                'table'      => 'purchase',
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
                'page'         => 'PurchaseIndex',
                'name'         => '供应厂商',
                'table'        => 'purchase',
                'field'        => 'supplier_id',
                'field_type'   => 'int',
                'component'    => 'input',
                'operators'    => json_encode([
                    ['text' => '包含', 'value' => 'like']
                ]),
                'query_config' => json_encode([
                    [
                        'operator' => 'like',
                        'joins'    => [
                            [
                                'table'    => 'supplier',
                                'first'    => 'supplier.id',
                                'operator' => '=',
                                'second'   => 'purchase.supplier_id',
                                'type'     => 'left'
                            ],
                        ],
                        'wheres'   => [
                            [
                                'type'     => 'whereRaw',
                                'sql'      => "cy_supplier.keyword LIKE ?",
                                'bindings' => [
                                    '%{$value}%'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
        ];
    }

    /**
     * 获取[顾客项目明细表]页面搜索字段配置
     * @return array
     */
    private function getReportCustomerProductConfig(): array
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

    /**
     * 获取[顾客商品明细表]页面搜索字段配置
     * @return array
     */
    private function getReportCustomerGoodsConfig(): array
    {
        return [
            [
                'page'             => 'ReportCustomerGoods',
                'name'             => '收费日期',
                'table'            => 'customer_goods',
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
                'page'       => 'ReportCustomerGoods',
                'name'       => '物品名称',
                'table'      => 'customer_goods',
                'field'      => 'goods_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'       => 'ReportCustomerGoods',
                'name'       => '套餐名称',
                'table'      => 'customer_goods',
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
                'page'             => 'ReportCustomerGoods',
                'name'             => '单据状态',
                'table'            => 'customer_goods',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.customer_goods.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
        ];
    }

    /**
     * 获取[预约列表]页面搜索字段配置
     * @return array
     */
    private function getWorkbenchAppointmentConfig(): array
    {
        return [
            [
                'page'             => 'WorkbenchAppointment',
                'name'             => '预约类型',
                'table'            => 'appointments',
                'field'            => 'type',
                'field_type'       => 'varchar',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'  => true,
                        'clearable' => true,
                    ],
                    'options' => collect(AppointmentType::options())->map(fn($label, $value) => ['label' => $label, 'value' => $value])->values()->all()
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            [
                'page'             => 'WorkbenchAppointment',
                'name'             => '预约状态',
                'table'            => 'appointments',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'  => true,
                        'clearable' => true,
                    ],
                    'options' => collect(AppointmentStatus::options())->map(fn($label, $value) => ['label' => $label, 'value' => $value])->values()->all()
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            [
                'page'             => 'WorkbenchAppointment',
                'name'             => '预约科室',
                'table'            => 'appointments',
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
                'page'       => 'WorkbenchAppointment',
                'name'       => '预约顾问',
                'table'      => 'appointments',
                'field'      => 'consultant_id',
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
                'page'       => 'WorkbenchAppointment',
                'name'       => '预约医生',
                'table'      => 'appointments',
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
                'page'       => 'WorkbenchAppointment',
                'name'       => '预约备注',
                'table'      => 'appointments',
                'field'      => 'remark',
                'field_type' => 'text',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'       => 'WorkbenchAppointment',
                'name'       => '录单人员',
                'table'      => 'appointments',
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
        ];
    }

    /**
     * 获取[顾客退款明细表]页面搜索字段配置
     * @return array
     */
    private function getReportCustomerRefundConfig(): array
    {
        return [
            [
                'page'       => 'ReportCustomerRefund',
                'name'       => '项目名称',
                'table'      => 'cashier_refund_detail',
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
                'page'       => 'ReportCustomerRefund',
                'name'       => '物品名称',
                'table'      => 'cashier_refund_detail',
                'field'      => 'goods_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'       => 'ReportCustomerRefund',
                'name'       => '套餐名称',
                'table'      => 'cashier_refund_detail',
                'field'      => 'package_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'             => 'ReportCustomerRefund',
                'name'             => '结算科室',
                'table'            => 'cashier_refund_detail',
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
                'page'       => 'ReportCustomerRefund',
                'name'       => '退款备注',
                'table'      => 'cashier_refund_detail',
                'field'      => 'remark',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'ReportCustomerRefund',
                'name'       => '收银人员',
                'table'      => 'cashier_refund_detail',
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
                'page'             => 'ReportCustomerRefund',
                'name'             => '收银时间',
                'table'            => 'cashier_refund_detail',
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
        ];
    }

    /**
     * 获取[退货出库]页面搜索字段配置
     * @return array
     */
    private function getPurchaseReturnIndexConfig(): array
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

    private function getDepartmentPickingConfig(): array
    {
        return [
            [
                'page'       => 'DepartmentPickingIndex',
                'name'       => '单据编号',
                'table'      => 'department_picking',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'DepartmentPickingIndex',
                'name'             => '领料类别',
                'table'            => 'department_picking',
                'field'            => 'type_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/department-picking-type',
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
                'page'             => 'DepartmentPickingIndex',
                'name'             => '单据状态',
                'table'            => 'department_picking',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.department_picking.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'DepartmentPickingIndex',
                'name'             => '出库仓库',
                'table'            => 'department_picking',
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
                'page'             => 'DepartmentPickingIndex',
                'name'             => '领料科室',
                'table'            => 'department_picking',
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
                'page'       => 'DepartmentPickingIndex',
                'name'       => '领料人员',
                'table'      => 'department_picking',
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
                'page'       => 'DepartmentPickingIndex',
                'name'       => '录单人员',
                'table'      => 'department_picking',
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
                'page'             => 'DepartmentPickingIndex',
                'name'             => '单据日期',
                'table'            => 'department_picking',
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
                'page'             => 'DepartmentPickingIndex',
                'name'             => '录单日期',
                'table'            => 'department_picking',
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
                'page'             => 'DepartmentPickingIndex',
                'name'             => '审核日期',
                'table'            => 'department_picking',
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
                'page'       => 'DepartmentPickingIndex',
                'name'       => '领料备注',
                'table'      => 'department_picking',
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

    private function getInventoryIndexConfig(): array
    {
        return [
            [
                'page'       => 'InventoryIndex',
                'name'       => '库存数量',
                'table'      => 'inventory',
                'field'      => 'number',
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
                'page'       => 'InventoryIndex',
                'name'       => '库存成本',
                'table'      => 'inventory',
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
                'page'             => 'InventoryIndex',
                'name'             => '仓库名称',
                'table'            => 'inventory',
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
        ];
    }

    /**
     * 获取[批次管理]页面搜索字段配置
     * @return array
     */
    private function getInventoryBatchsIndexConfig(): array
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

    private function getInventoryTransferIndexConfig(): array
    {
        return [
            [
                'page'       => 'InventoryTransferIndex',
                'name'       => '单据号',
                'table'      => 'inventory_transfer',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'InventoryTransferIndex',
                'name'             => '单据日期',
                'table'            => 'inventory_transfer',
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
                'page'             => 'InventoryTransferIndex',
                'name'             => '单据状态',
                'table'            => 'inventory_transfer',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.inventory_transfer.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'InventoryTransferIndex',
                'name'             => '出库仓库',
                'table'            => 'inventory_transfer',
                'field'            => 'out_warehouse_id',
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
                'page'             => 'InventoryTransferIndex',
                'name'             => '入库仓库',
                'table'            => 'inventory_transfer',
                'field'            => 'in_warehouse_id',
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
                'page'       => 'InventoryTransferIndex',
                'name'       => '录单人员',
                'table'      => 'inventory_transfer',
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
                'page'       => 'InventoryTransferIndex',
                'name'       => '经办人员',
                'table'      => 'inventory_transfer',
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
                'page'       => 'InventoryTransferIndex',
                'name'       => '审核人员',
                'table'      => 'inventory_transfer',
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
                'page'             => 'InventoryTransferIndex',
                'name'             => '审核日期',
                'table'            => 'inventory_transfer',
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
                'page'             => 'InventoryTransferIndex',
                'name'             => '录单日期',
                'table'            => 'inventory_transfer',
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
        ];
    }

    private function getInventoryLossIndexConfig(): array
    {
        return [
            [
                'page'       => 'InventoryLossIndex',
                'name'       => '单据号',
                'table'      => 'inventory_losses',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'InventoryLossIndex',
                'name'             => '单据日期',
                'table'            => 'inventory_losses',
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
                'page'             => 'InventoryLossIndex',
                'name'             => '单据状态',
                'table'            => 'inventory_losses',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.inventory_losses.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'InventoryLossIndex',
                'name'             => '报损仓库',
                'table'            => 'inventory_losses',
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
                'page'             => 'InventoryLossIndex',
                'name'             => '报损科室',
                'table'            => 'inventory_losses',
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
                'page'       => 'InventoryLossIndex',
                'name'       => '录单人员',
                'table'      => 'inventory_losses',
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
                'page'       => 'InventoryLossIndex',
                'name'       => '经办人员',
                'table'      => 'inventory_losses',
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
                'page'       => 'InventoryLossIndex',
                'name'       => '审核人员',
                'table'      => 'inventory_losses',
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
                'page'       => 'InventoryLossIndex',
                'name'       => '报损备注',
                'table'      => 'inventory_losses',
                'field'      => 'remark',
                'field_type' => 'text',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'             => 'InventoryLossIndex',
                'name'             => '审核日期',
                'table'            => 'inventory_losses',
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
                'page'             => 'InventoryLossIndex',
                'name'             => '录单日期',
                'table'            => 'inventory_losses',
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
        ];
    }

    private function getInventoryOverflowIndexConfig(): array
    {
        return [
            [
                'page'       => 'InventoryOverflowIndex',
                'name'       => '单据号',
                'table'      => 'inventory_overflows',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'InventoryOverflowIndex',
                'name'             => '单据日期',
                'table'            => 'inventory_overflows',
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
                'page'             => 'InventoryOverflowIndex',
                'name'             => '单据状态',
                'table'            => 'inventory_overflows',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.inventory_overflows.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
            [
                'page'             => 'InventoryOverflowIndex',
                'name'             => '报溢仓库',
                'table'            => 'inventory_overflows',
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
                'page'             => 'InventoryOverflowIndex',
                'name'             => '报溢科室',
                'table'            => 'inventory_overflows',
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
                'page'       => 'InventoryOverflowIndex',
                'name'       => '录单人员',
                'table'      => 'inventory_overflows',
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
                'page'       => 'InventoryOverflowIndex',
                'name'       => '经办人员',
                'table'      => 'inventory_overflows',
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
                'page'       => 'InventoryOverflowIndex',
                'name'       => '审核人员',
                'table'      => 'inventory_overflows',
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
                'page'       => 'InventoryOverflowIndex',
                'name'       => '报溢备注',
                'table'      => 'inventory_overflows',
                'field'      => 'remark',
                'field_type' => 'text',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'             => 'InventoryOverflowIndex',
                'name'             => '审核日期',
                'table'            => 'inventory_overflows',
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
                'page'             => 'InventoryOverflowIndex',
                'name'             => '录单日期',
                'table'            => 'inventory_overflows',
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
        ];
    }

    private function getReportPerformanceSalesConfig(): array
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

    private function getReportPurchaseDetailConfig(): array
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

    private function getReportDepartmentPickingDetail(): array
    {
        return [
            [
                'page'             => 'ReportDepartmentPickingDetail',
                'name'             => '单据日期',
                'table'            => 'department_picking_detail',
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
                'page'       => 'ReportDepartmentPickingDetail',
                'name'       => '单据编号',
                'table'      => 'department_picking_detail',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'ReportDepartmentPickingDetail',
                'name'             => '报损仓库',
                'table'            => 'department_picking_detail',
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
                'page'             => 'ReportDepartmentPickingDetail',
                'name'             => '领料科室',
                'table'            => 'department_picking_detail',
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
                'page'             => 'ReportDepartmentPickingDetail',
                'name'             => '领料类别',
                'table'            => 'department_picking',
                'field'            => 'type_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/department-picking-type',
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
                'page'       => 'ReportDepartmentPickingDetail',
                'name'       => '领料人员',
                'table'      => 'department_picking',
                'field'      => 'user_id',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page'       => 'ReportDepartmentPickingDetail',
                'name'       => '库存批号',
                'table'      => 'department_picking_detail',
                'field'      => 'batch_code',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ])
            ],
            [
                'page'       => 'ReportDepartmentPickingDetail',
                'name'       => 'SN码',
                'table'      => 'department_picking_detail',
                'field'      => 'sncode',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ])
            ],
            [
                'page'       => 'ReportDepartmentPickingDetail',
                'name'       => '生产厂家',
                'table'      => 'department_picking_detail',
                'field'      => 'manufacturer_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ])
            ],
            [
                'page'             => 'ReportDepartmentPickingDetail',
                'name'             => '生产日期',
                'table'            => 'department_picking_detail',
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
                'page'             => 'ReportDepartmentPickingDetail',
                'name'             => '录单日期',
                'table'            => 'department_picking_detail',
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
                'page'       => 'ReportDepartmentPickingDetail',
                'name'       => '录单人员',
                'table'      => 'department_picking',
                'field'      => 'create_user_id',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'       => 'ReportDepartmentPickingDetail',
                'name'       => '审核人员',
                'table'      => 'department_picking',
                'field'      => 'check_user',
                'field_type' => 'int',
                'component'  => 'user',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'             => 'ReportDepartmentPickingDetail',
                'name'             => '审核时间',
                'table'            => 'department_picking',
                'field'            => 'check_time',
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
                'page'             => 'ReportDepartmentPickingDetail',
                'name'             => '商品类别',
                'table'            => 'goods',
                'field'            => 'goods_type_id',
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
                ]),
                'query_config'     => json_encode([
                    [
                        'operator' => '=',
                        'joins'    => [
                            [
                                'table'    => 'goods',
                                'first'    => 'goods.id',
                                'operator' => '=',
                                'second'   => 'department_picking_detail.goods_id',
                                'type'     => 'left'
                            ]
                        ],
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
                    ]
                ])
            ]
        ];
    }

    private function getReportConsumableDetail(): array
    {
        return [
            [
                'page'       => 'ReportConsumableDetail',
                'name'       => '单据编号',
                'table'      => 'consumable_detail',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'ReportConsumableDetail',
                'name'             => '单据日期',
                'table'            => 'consumable_detail',
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
                'page'       => 'ReportConsumableDetail',
                'name'       => '消费项目',
                'table'      => 'consumable',
                'field'      => 'product_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page'             => 'ReportConsumableDetail',
                'name'             => '出库仓库',
                'table'            => 'consumable_detail',
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
                'page'             => 'ReportConsumableDetail',
                'name'             => '领料科室',
                'table'            => 'consumable_detail',
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
                'page'       => 'ReportConsumableDetail',
                'name'       => '批号',
                'table'      => 'consumable_detail',
                'field'      => 'batch_code',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '=']
                ])
            ],
            [
                'page'       => 'ReportConsumableDetail',
                'name'       => '生产厂家',
                'table'      => 'consumable_detail',
                'field'      => 'manufacturer_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'             => 'ReportConsumableDetail',
                'name'             => '生产日期',
                'table'            => 'consumable_detail',
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
                'page'             => 'ReportConsumableDetail',
                'name'             => '过期时间',
                'table'            => 'consumable_detail',
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
            [
                'page'       => 'ReportConsumableDetail',
                'name'       => 'SN码',
                'table'      => 'consumable_detail',
                'field'      => 'sncode',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page'       => 'ReportConsumableDetail',
                'name'       => '备注',
                'table'      => 'consumable_detail',
                'field'      => 'remark',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
        ];
    }
}
