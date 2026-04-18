<?php

namespace Database\Seeders\Tenant\SceneFields;

use App\Enums\CashierRetailStatus;
use Illuminate\Support\Facades\DB;

class CashierRetailSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        $prefix = DB::getTablePrefix();

        return [
            [
                'page' => 'CashierRetailIndex',
                'name' => '单据状态',
                'table' => 'cashier_retail',
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
                    'options' => collect(CashierRetailStatus::options())
                        ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
                        ->values()
                        ->all(),
                ]),
            ],
            [
                'page' => 'CashierRetailIndex',
                'name' => '收费单号',
                'table' => 'cashier_retail',
                'field' => 'cashier_id',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CashierRetailIndex',
                'name' => '接诊类型',
                'table' => 'cashier_retail',
                'field' => 'type',
                'field_type' => 'tinyint',
                'component' => 'select',
                'api' => '/cache/reception-type',
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CashierRetailIndex',
                'name' => '应收金额',
                'table' => 'cashier_retail',
                'field' => 'payable',
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
                'page' => 'CashierRetailIndex',
                'name' => '实收金额',
                'table' => 'cashier_retail',
                'field' => 'income',
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
                'page' => 'CashierRetailIndex',
                'name' => '余额支付',
                'table' => 'cashier_retail',
                'field' => 'deposit',
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
                'page' => 'CashierRetailIndex',
                'name' => '欠款金额',
                'table' => 'cashier_retail',
                'field' => 'arrearage',
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
                'page' => 'CashierRetailIndex',
                'name' => '收银人员',
                'table' => 'cashier_retail',
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
                'page' => 'CashierRetailIndex',
                'name' => '媒介',
                'table' => 'cashier_retail',
                'field' => 'medium_id',
                'field_type' => 'int',
                'component' => 'cascader',
                'api' => '/cache/mediums?cascader=1',
                'component_params' => json_encode([
                    'props' => [
                        'props' => [
                            'label' => 'text',
                            'value' => 'id',
                            'checkStrictly' => true,
                        ],
                        'clearable' => true,
                        'filterable' => true,
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
                'query_config' => json_encode([
                    [
                        'operator' => '=',
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "({$prefix}cashier_retail.medium_id IN (select id from {$prefix}medium where tree LIKE CONCAT((SELECT tree FROM {$prefix}medium WHERE id = ?), '-%') OR {$prefix}medium.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}',
                                ],
                            ],
                        ],
                    ],
                    [
                        'operator' => '<>',
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "({$prefix}cashier_retail.medium_id NOT IN (select id from {$prefix}medium where tree LIKE CONCAT((SELECT tree FROM {$prefix}medium WHERE id = ?), '-%') OR {$prefix}medium.id = ?))",
                                'bindings' => [
                                    '{$value[-1]}',
                                    '{$value[-1]}',
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
            [
                'page' => 'CashierRetailIndex',
                'name' => '备注',
                'table' => 'cashier_retail',
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
            ],
            [
                'page' => 'CashierRetailIndex',
                'name' => '创建时间',
                'table' => 'cashier_retail',
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
                'page' => 'CashierRetailIndex',
                'name' => '更新时间',
                'table' => 'cashier_retail',
                'field' => 'updated_at',
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
        ];
    }
}
