<?php

namespace Database\Seeders\Tenant\SceneFields;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;

class WorkbenchTodaySeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'WorkbenchToday',
                'name'             => '就诊状态',
                'table'            => 'appointments',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'   => true,
                        'clearable'  => true,
                        'filterable' => true
                    ],
                    'options' => collect(AppointmentStatus::options())->map(fn($label, $value) => ['label' => $label, 'value' => $value])->values()->all()
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            [
                'page'             => 'WorkbenchToday',
                'name'             => '预约类型',
                'table'            => 'appointments',
                'field'            => 'type',
                'field_type'       => 'varchar',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'   => true,
                        'clearable'  => true,
                        'filterable' => true
                    ],
                    'options' => collect(AppointmentType::options())->map(fn($label, $value) => ['label' => $label, 'value' => $value])->values()->all()
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            [
                'page'             => 'WorkbenchToday',
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
                'page'       => 'WorkbenchToday',
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
                'page'       => 'WorkbenchToday',
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
                'page'       => 'WorkbenchToday',
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
                'page'       => 'WorkbenchToday',
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
}
