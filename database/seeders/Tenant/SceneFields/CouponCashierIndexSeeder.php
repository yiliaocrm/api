<?php

namespace Database\Seeders\Tenant\SceneFields;

class CouponCashierIndexSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page' => 'CouponCashierIndex',
                'name' => '收费单号',
                'table' => 'cashier_coupons',
                'field' => 'cashier_id',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CouponCashierIndex',
                'name' => '卡券编号',
                'table' => 'cashier_coupons',
                'field' => 'coupon_number',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CouponCashierIndex',
                'name' => '卡券名称',
                'table' => 'cashier_coupons',
                'field' => 'coupon_name',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CouponCashierIndex',
                'name' => '使用金额',
                'table' => 'cashier_coupons',
                'field' => 'income',
                'field_type' => 'decimal',
                'component' => 'numberbox',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '介于', 'value' => 'between'],
                ]),
            ],
            [
                'page' => 'CouponCashierIndex',
                'name' => '备注信息',
                'table' => 'cashier_coupons',
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
        ];
    }
}
