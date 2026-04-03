<?php

namespace Database\Seeders\Tenant\SceneFields;

use Illuminate\Support\Facades\DB;

class CashierRefundSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        $prefix = DB::getTablePrefix();

        return [
            [
                'page' => 'CashierRefundIndex',
                'name' => '单据状态',
                'table' => 'cashier_refund',
                'field' => 'status',
                'field_type' => 'tinyint',
                'component' => 'select',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                        'filterable' => true,
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.cashier_refund.status'),
                ]),
            ],
            [
                'page' => 'CashierRefundIndex',
                'name' => '退款单号',
                'table' => 'cashier_refund',
                'field' => 'id',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CashierRefundIndex',
                'name' => '退款总额',
                'table' => 'cashier_refund',
                'field' => 'amount',
                'field_type' => 'decimal',
                'component' => 'input-number',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ]),
            ],
            [
                'page' => 'CashierRefundIndex',
                'name' => '收银人员',
                'table' => 'cashier_refund',
                'field' => 'user_id',
                'field_type' => 'int',
                'component' => 'user',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null'],
                ]),
            ],
            [
                'page' => 'CashierRefundIndex',
                'name' => '录单时间',
                'table' => 'cashier_refund',
                'field' => 'created_at',
                'field_type' => 'timestamp',
                'component' => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type' => 'date',
                        'value-format' => 'YYYY-MM-DD',
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between'],
                ]),
            ],
            [
                'page' => 'CashierRefundIndex',
                'name' => '退款备注',
                'table' => 'cashier_refund',
                'field' => 'remark',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null'],
                ]),
                'query_config' => json_encode([
                    [
                        'operator' => 'like',
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "({$prefix}cashier_refund.remark LIKE ? OR EXISTS (SELECT 1 FROM {$prefix}cashier_refund_detail WHERE {$prefix}cashier_refund_detail.cashier_refund_id = {$prefix}cashier_refund.id AND {$prefix}cashier_refund_detail.remark LIKE ?))",
                                'bindings' => ['%{$value}%', '%{$value}%'],
                            ],
                        ],
                    ],
                    [
                        'operator' => '=',
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "({$prefix}cashier_refund.remark = ? OR EXISTS (SELECT 1 FROM {$prefix}cashier_refund_detail WHERE {$prefix}cashier_refund_detail.cashier_refund_id = {$prefix}cashier_refund.id AND {$prefix}cashier_refund_detail.remark = ?))",
                                'bindings' => ['{$value}', '{$value}'],
                            ],
                        ],
                    ],
                    [
                        'operator' => '<>',
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "(({$prefix}cashier_refund.remark <> ? OR {$prefix}cashier_refund.remark IS NULL) AND NOT EXISTS (SELECT 1 FROM {$prefix}cashier_refund_detail WHERE {$prefix}cashier_refund_detail.cashier_refund_id = {$prefix}cashier_refund.id AND {$prefix}cashier_refund_detail.remark = ?))",
                                'bindings' => ['{$value}', '{$value}'],
                            ],
                        ],
                    ],
                    [
                        'operator' => 'is null',
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "{$prefix}cashier_refund.remark IS NULL AND NOT EXISTS (SELECT 1 FROM {$prefix}cashier_refund_detail WHERE {$prefix}cashier_refund_detail.cashier_refund_id = {$prefix}cashier_refund.id AND {$prefix}cashier_refund_detail.remark IS NOT NULL)",
                            ],
                        ],
                    ],
                    [
                        'operator' => 'is not null',
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "({$prefix}cashier_refund.remark IS NOT NULL OR EXISTS (SELECT 1 FROM {$prefix}cashier_refund_detail WHERE {$prefix}cashier_refund_detail.cashier_refund_id = {$prefix}cashier_refund.id AND {$prefix}cashier_refund_detail.remark IS NOT NULL))",
                            ],
                        ],
                    ],
                ]),
            ],
        ];
    }
}
