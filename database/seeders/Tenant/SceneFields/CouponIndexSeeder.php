<?php

namespace Database\Seeders\Tenant\SceneFields;

class CouponIndexSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page' => 'CouponIndex',
                'name' => '卡券状态',
                'table' => 'coupons',
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
                    'options' => $this->convertSettingConfigToOptions('setting.coupon.status'),
                ]),
            ],
            [
                'page' => 'CouponIndex',
                'name' => '卡券面值',
                'table' => 'coupons',
                'field' => 'coupon_value',
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
                'page' => 'CouponIndex',
                'name' => '发放总量',
                'table' => 'coupons',
                'field' => 'total',
                'field_type' => 'int',
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
                'page' => 'CouponIndex',
                'name' => '已领取',
                'table' => 'coupons',
                'field' => 'issue_count',
                'field_type' => 'int',
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
                'page' => 'CouponIndex',
                'name' => '已使用',
                'table' => 'coupons',
                'field' => 'consume_count',
                'field_type' => 'int',
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
                'page' => 'CouponIndex',
                'name' => '多次使用',
                'table' => 'coupons',
                'field' => 'multiple_use',
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
                    'options' => [
                        ['label' => '是', 'value' => 1],
                        ['label' => '否', 'value' => 0],
                    ],
                ]),
            ],
            [
                'page' => 'CouponIndex',
                'name' => '卡券售价',
                'table' => 'coupons',
                'field' => 'sales_price',
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
                'page' => 'CouponIndex',
                'name' => '兑换积分',
                'table' => 'coupons',
                'field' => 'integrals',
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
                'page' => 'CouponIndex',
                'name' => '发行时间',
                'table' => 'coupons',
                'field' => 'start',
                'field_type' => 'datetime',
                'component' => 'datebox',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '介于', 'value' => 'between'],
                ]),
                'component_params' => json_encode([
                    'props' => [
                        'type' => 'date',
                    ],
                ]),
            ],
            [
                'page' => 'CouponIndex',
                'name' => '结束时间',
                'table' => 'coupons',
                'field' => 'end',
                'field_type' => 'datetime',
                'component' => 'datebox',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '介于', 'value' => 'between'],
                ]),
                'component_params' => json_encode([
                    'props' => [
                        'type' => 'date',
                    ],
                ]),
            ],
            [
                'page' => 'CouponIndex',
                'name' => '创建时间',
                'table' => 'coupons',
                'field' => 'created_at',
                'field_type' => 'datetime',
                'component' => 'datebox',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '介于', 'value' => 'between'],
                ]),
                'component_params' => json_encode([
                    'props' => [
                        'type' => 'date',
                    ],
                ]),
            ],
            [
                'page' => 'CouponIndex',
                'name' => '创建人员',
                'table' => 'coupons',
                'field' => 'create_user_id',
                'field_type' => 'int',
                'component' => 'user',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
        ];
    }
}
