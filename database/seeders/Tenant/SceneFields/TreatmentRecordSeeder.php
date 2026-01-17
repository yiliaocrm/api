<?php

namespace Database\Seeders\Tenant\SceneFields;

use App\Enums\TreatmentStatus;

class TreatmentRecordSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'TreatmentRecord',
                'name'             => '状态',
                'table'            => 'treatment',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'  => true,
                        'clearable' => true,
                    ],
                    'options' => collect(TreatmentStatus::options())->map(fn($label, $value) => ['label' => $label, 'value' => $value])->values()->all()
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            [
                'page'             => 'TreatmentRecord',
                'name'             => '项目分类',
                'table'            => 'treatment',
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
                                'second'   => 'treatment.product_id',
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
                                'second'   => 'treatment.product_id',
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
                'page'       => 'TreatmentRecord',
                'name'       => '项目名称',
                'table'      => 'treatment',
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
                'page'       => 'TreatmentRecord',
                'name'       => '套餐名称',
                'table'      => 'treatment',
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
                'page'             => 'TreatmentRecord',
                'name'             => '执行科室',
                'table'            => 'treatment',
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
                'page'       => 'TreatmentRecord',
                'name'       => '划扣备注',
                'table'      => 'treatment',
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
            [
                'page'         => 'TreatmentRecord',
                'name'         => '配台人员',
                'table'        => 'treatment',
                'field'        => 'participants',
                'field_type'   => 'int',
                'component'    => 'user',
                'operators'    => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
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
                                'type'     => 'inner'
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
                    [
                        'operator' => '<>',
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
                                'operator' => '<>',
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is null',
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
                                'type'   => 'whereNull',
                                'column' => 'treatment_participants.user_id',
                            ]
                        ]
                    ],
                    [
                        'operator' => 'is not null',
                        'joins'    => [
                            [
                                'table'    => 'treatment_participants',
                                'first'    => 'treatment_participants.treatment_id',
                                'operator' => '=',
                                'second'   => 'treatment.id',
                                'type'     => 'inner'
                            ]
                        ],
                        'wheres'   => [
                            [
                                'type'   => 'whereNotNull',
                                'column' => 'treatment_participants.user_id',
                            ]
                        ]
                    ]
                ])
            ],
            [
                'page'       => 'TreatmentRecord',
                'name'       => '划扣人员',
                'table'      => 'treatment',
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
