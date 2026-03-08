<?php

namespace Database\Seeders\Tenant;

use App\Models\WorkflowComponent;
use App\Models\WorkflowComponentType;
use Illuminate\Database\Seeder;

class WorkflowComponentSeeder extends Seeder
{
    public function run(): void
    {
        WorkflowComponent::query()->truncate();

        $types = WorkflowComponentType::query()->pluck('id', 'key');

        $components = [
            [
                'key' => 'start_trigger',
                'name' => '触发型旅程',
                'icon' => 'el-icon-pointer',
                'bg_color' => '#4b65fd',
                'description' => '当客户发生指定行为后触发执行。',
                'type_id' => $types['start'],
                'template' => [
                    'parameters' => [
                        'name' => '',
                        'journeyType' => 'trigger',
                        'targetPeople' => null,
                        'triggerEvents' => null,
                    ],
                    'preview_samples' => [
                        'customer.created' => [
                            'payload' => [
                                'id' => 'ffffce38-cd15-4eec-b3ad-ff774e7d104c',
                                'qq' => 'V2银星会员',
                                'age' => null,
                                'sex' => 2,
                                'sfz' => null,
                                'name' => '张三',
                                'amount' => 34568,
                                'idcard' => '202404270049',
                                'job_id' => null,
                                'remark' => '24',
                                'wechat' => null,
                                'balance' => 0,
                                'marital' => null,
                                'user_id' => 1,
                                'birthday' => null,
                                'integral' => 46.28,
                                'level_id' => 1,
                                'arrearage' => 0,
                                'doctor_id' => null,
                                'last_time' => '2025-02-28 14:12:52',
                                'medium_id' => 13,
                                'address_id' => 7,
                                'ascription' => 24,
                                'consultant' => 24,
                                'created_at' => '2024-04-27 13:10:00',
                                'first_time' => '2024-04-27 13:17:21',
                                'service_id' => null,
                                'updated_at' => '2026-02-22 19:44:50',
                                'economic_id' => null,
                                'file_number' => 'WZ4062306',
                                'department_id' => 0,
                                'last_followup' => '2025-07-15 02:51:03',
                                'total_payment' => 34568,
                                'last_treatment' => '2025-01-09 08:59:09',
                                'expend_integral' => '0.0000',
                                'referrer_user_id' => null,
                                'referrer_customer_id' => null,
                            ],
                            'started' => true,
                            'trigger' => [
                                'event' => 'customer.created',
                                'model_id' => 'ffffce38-cd15-4eec-b3ad-ff774e7d104c',
                                'model_type' => 'customer',
                                'triggered_at' => now()->toIso8601String(),
                            ],
                            'trigger_events' => [
                                'customer.created',
                                'customer.updated',
                            ],
                        ],
                        'customer.updated' => [
                            'payload' => [
                                'id' => 'ffffce38-cd15-4eec-b3ad-ff774e7d104c',
                                'qq' => 'V2银星会员',
                                'age' => null,
                                'sex' => 2,
                                'sfz' => null,
                                'name' => '张三',
                                'amount' => 34568,
                                'idcard' => '202404270049',
                                'job_id' => null,
                                'remark' => '24',
                                'wechat' => null,
                                'balance' => 0,
                                'marital' => null,
                                'user_id' => 1,
                                'birthday' => null,
                                'integral' => 46.28,
                                'level_id' => 1,
                                'arrearage' => 0,
                                'doctor_id' => null,
                                'last_time' => '2025-02-28 14:12:52',
                                'medium_id' => 13,
                                'address_id' => 7,
                                'ascription' => 24,
                                'consultant' => 24,
                                'created_at' => '2024-04-27 13:10:00',
                                'first_time' => '2024-04-27 13:17:21',
                                'service_id' => null,
                                'updated_at' => '2026-02-22 19:44:50',
                                'economic_id' => null,
                                'file_number' => 'WZ4062306',
                                'department_id' => 0,
                                'last_followup' => '2025-07-15 02:51:03',
                                'total_payment' => 34568,
                                'last_treatment' => '2025-01-09 08:59:09',
                                'expend_integral' => '0.0000',
                                'referrer_user_id' => null,
                                'referrer_customer_id' => null,
                            ],
                            'started' => true,
                            'trigger' => [
                                'event' => 'customer.updated',
                                'model_id' => 'ffffce38-cd15-4eec-b3ad-ff774e7d104c',
                                'model_type' => 'customer',
                                'triggered_at' => now()->toIso8601String(),
                            ],
                            'trigger_events' => [
                                'customer.created',
                                'customer.updated',
                            ],
                        ],
                        'reservation.created' => [
                            'payload' => [
                                'id' => 1,
                                'customer_id' => 1,
                                'date' => now()->toDateString(),
                                'time' => now()->format('H:i:s'),
                                'status' => 'pending',
                                'created_at' => now()->toIso8601String(),
                                'updated_at' => now()->toIso8601String(),
                            ],
                            'started' => true,
                            'trigger' => [
                                'event' => 'reservation.created',
                                'model_id' => '1',
                                'model_type' => 'reservation',
                                'triggered_at' => now()->toIso8601String(),
                            ],
                            'trigger_events' => [
                                'customer.created',
                                'customer.updated',
                            ],
                        ],
                        'reservation.updated' => [
                            'payload' => [
                                'id' => 1,
                                'customer_id' => 1,
                                'date' => now()->toDateString(),
                                'time' => now()->format('H:i:s'),
                                'status' => 'confirmed',
                                'created_at' => now()->subDay()->toIso8601String(),
                                'updated_at' => now()->toIso8601String(),
                            ],
                            'started' => true,
                            'trigger' => [
                                'event' => 'reservation.updated',
                                'model_id' => '1',
                                'model_type' => 'reservation',
                                'triggered_at' => now()->toIso8601String(),
                            ],
                            'trigger_events' => [
                                'customer.created',
                                'customer.updated',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'start_periodic',
                'name' => '周期型旅程',
                'icon' => 'sc-icon-cycle',
                'bg_color' => '#2ab984',
                'description' => '按固定周期自动触发执行。',
                'type_id' => $types['start'],
                'template' => [
                    'parameters' => [
                        'journeyType' => 'periodic',
                        'targetPeople' => null,
                        'runTime' => 'day',
                        'dayInterval' => 1,
                        'weekInterval' => 1,
                        'weekDays' => [],
                        'monthInterval' => 1,
                        'monthDays' => [],
                        'executeTime' => '09:00',
                    ],
                ],
            ],
            [
                'key' => 'wait',
                'name' => '等待',
                'icon' => 'el-icon-timer',
                'bg_color' => '#f1bc5f',
                'description' => '暂停流程，在指定时间后继续执行。',
                'type_id' => $types['flow_control'],
                'template' => [
                    'parameters' => [
                        'mode' => 'after',
                        'time' => null,
                        'delay' => 1,
                        'unit' => 'minutes',
                        'overwrite' => false,
                    ],
                ],
            ],
            [
                'key' => 'log',
                'name' => '日志',
                'icon' => 'el-icon-document',
                'bg_color' => '#10b981',
                'description' => '记录执行日志，便于排查流程运行情况。',
                'type_id' => $types['flow_control'],
                'template' => [
                    'parameters' => [
                        'message' => 'workflow execution {{trigger.event}} model {{trigger.model_id}}',
                        'with_context' => true,
                    ],
                ],
                'output_schema' => [
                    [
                        'field' => 'logged',
                        'type' => 'boolean',
                        'description' => '是否已写入日志',
                    ],
                    [
                        'field' => 'message',
                        'type' => 'string',
                        'description' => '日志消息内容',
                    ],
                    [
                        'field' => 'with_context',
                        'type' => 'boolean',
                        'description' => '是否输出上下文数据',
                    ],
                    [
                        'field' => 'workflow_context',
                        'type' => 'object',
                        'description' => '上下文快照（with_context 为 true 时输出）',
                    ],
                ],
            ],
            [
                'key' => 'condition_business',
                'name' => '业务判断',
                'icon' => 'el-icon-share',
                'bg_color' => '#8B5CF6',
                'description' => '根据业务数据判断命中不同分支。',
                'type_id' => $types['condition'],
                'template' => [
                    'parameters' => [
                        'conditionType' => 'business',
                        'groups' => [
                            [
                                'id' => 'default_group',
                                'name' => '条件1',
                                'matchType' => 'all',
                                'rules' => [],
                            ],
                        ],
                        'defaultLabel' => '其他',
                    ],
                ],
                'output_schema' => [
                    [
                        'field' => 'matched',
                        'type' => 'boolean',
                        'description' => '是否命中条件分支',
                    ],
                    [
                        'field' => 'matched_branch',
                        'type' => 'string',
                        'description' => '命中的分支端口',
                    ],
                    [
                        'field' => 'matched_group_index',
                        'type' => 'integer',
                        'description' => '命中的条件组索引',
                    ],
                ],
            ],
            [
                'key' => 'create_followup',
                'name' => '回访任务',
                'icon' => 'el-icon-phone',
                'bg_color' => '#10B981',
                'description' => '自动创建客户回访任务',
                'type_id' => $types['task_delivery'],
                'template' => [
                    'parameters' => [
                        'title' => '',
                        'type' => null,
                        'followup_user_mode' => 'specified',
                        'followup_user' => null,
                        'followup_user_relation' => null,
                        'followup_user_fallback' => false,
                        'followup_user_fallback_user' => null,
                        'date_mode' => 'relative',
                        'date_offset' => 1,
                        'date_unit' => 'days',
                        'absolute_date' => null,
                    ],
                ],
                'output_schema' => [
                    [
                        'field' => 'followup_id',
                        'type' => 'string',
                        'description' => '创建的回访任务ID',
                    ],
                    [
                        'field' => 'created',
                        'type' => 'boolean',
                        'description' => '是否创建成功',
                    ],
                ],
            ],
        ];

        foreach ($components as $component) {
            WorkflowComponent::query()->create($component);
        }
    }
}
