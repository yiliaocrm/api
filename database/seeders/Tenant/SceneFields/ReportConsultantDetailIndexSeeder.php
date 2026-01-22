<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportConsultantDetailIndexSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            // 成交状态 - 来自 reception 表
            [
                'page'             => 'ReportConsultantDetailIndex',
                'name'             => '成交状态',
                'table'            => 'reception',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'  => true,
                        'clearable' => true,
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.reception.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 接诊类型 - 来自 reception 表
            [
                'page'             => 'ReportConsultantDetailIndex',
                'name'             => '接诊类型',
                'table'            => 'reception',
                'field'            => 'type',
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
            // 已接待 - 来自 reception 表
            [
                'page'             => 'ReportConsultantDetailIndex',
                'name'             => '已接待',
                'table'            => 'reception',
                'field'            => 'receptioned',
                'field_type'       => 'int',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true,
                        'multiple'  => true,
                    ],
                    'options' => [
                        ['label' => '是', 'value' => 1],
                        ['label' => '否', 'value' => 0],
                    ]
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 咨询科室 - 来自 reception 表
            [
                'page'             => 'ReportConsultantDetailIndex',
                'name'             => '咨询科室',
                'table'            => 'reception',
                'field'            => 'department_id',
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
            // 咨询项目 - 来自 reception_items 关联表
            [
                'page'             => 'ReportConsultantDetailIndex',
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
            // 未成交原因 - 来自 reception 表
            [
                'page'             => 'ReportConsultantDetailIndex',
                'name'             => '未成交原因',
                'table'            => 'reception',
                'field'            => 'failure_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/failures',
                'component_params' => json_encode([
                    'props' => [
                        'clearable'  => true,
                        'filterable' => true,
                        'multiple'   => true,
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 咨询备注 - 来自 reception 表
            [
                'page'       => 'ReportConsultantDetailIndex',
                'name'       => '咨询备注',
                'table'      => 'reception',
                'field'      => 'remark',
                'field_type' => 'text',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 媒介来源 - 来自 reception 表
            [
                'page'             => 'ReportConsultantDetailIndex',
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
            // 现场咨询 - 来自 reception 表
            [
                'page'       => 'ReportConsultantDetailIndex',
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
            // 二开人员 - 来自 reception 表
            [
                'page'       => 'ReportConsultantDetailIndex',
                'name'       => '二开人员',
                'table'      => 'reception',
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
            // 助诊医生 - 来自 reception 表
            [
                'page'       => 'ReportConsultantDetailIndex',
                'name'       => '助诊医生',
                'table'      => 'reception',
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
            // 分诊接待 - 来自 reception 表
            [
                'page'        => 'ReportConsultantDetailIndex',
                'name'        => '分诊接待',
                'table'       => 'reception',
                'field'       => 'reception',
                'field_alias' => 'reception_user',
                'field_type'  => 'int',
                'component'   => 'user',
                'operators'   => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 录单人员 - 来自 reception 表
            [
                'page'       => 'ReportConsultantDetailIndex',
                'name'       => '录单人员',
                'table'      => 'reception',
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
