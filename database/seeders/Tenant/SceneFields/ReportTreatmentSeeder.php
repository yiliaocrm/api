<?php

namespace Database\Seeders\Tenant\SceneFields;

use App\Enums\TreatmentStatus;

class ReportTreatmentSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'ReportTreatmentDetail',
                'name'             => '划扣状态',
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
                'page'       => 'ReportTreatmentDetail',
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
                'page'       => 'ReportTreatmentDetail',
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
                'page'       => 'ReportTreatmentDetail',
                'name'       => '划扣次数',
                'table'      => 'treatment',
                'field'      => 'times',
                'field_type' => 'int',
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
                'page'       => 'ReportTreatmentDetail',
                'name'       => '划扣价格',
                'table'      => 'treatment',
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
                'page'       => 'ReportTreatmentDetail',
                'name'       => '欠款金额',
                'table'      => 'treatment',
                'field'      => 'arrearage',
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
                'page'             => 'ReportTreatmentDetail',
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
                'page'       => 'ReportTreatmentDetail',
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
                'page'         => 'ReportTreatmentDetail',
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
                'page'       => 'ReportTreatmentDetail',
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
