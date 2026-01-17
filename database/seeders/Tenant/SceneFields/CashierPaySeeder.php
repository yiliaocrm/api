<?php

namespace Database\Seeders\Tenant\SceneFields;

class CashierPaySeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'       => 'CashierPayIndex',
                'name'       => '付款金额',
                'table'      => 'cashier_pay',
                'field'      => 'income',
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
                'page'             => 'CashierPayIndex',
                'name'             => '支付方式',
                'table'            => 'cashier_pay',
                'field'            => 'accounts_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/accounts',
                'component_params' => json_encode([
                    'props' => [
                        'multiple'   => true,
                        'clearable'  => true,
                        'filterable' => true
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            [
                'page'       => 'CashierPayIndex',
                'name'       => '收费单号',
                'table'      => 'cashier_pay',
                'field'      => 'cashier_id',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'       => 'CashierPayIndex',
                'name'       => '备注信息',
                'table'      => 'cashier_pay',
                'field'      => 'remark',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '等于', 'value' => 'like'],
                    ['text' => '不等于', 'value' => 'not like'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            [
                'page'       => 'CashierPayIndex',
                'name'       => '收银人员',
                'table'      => 'cashier_pay',
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
