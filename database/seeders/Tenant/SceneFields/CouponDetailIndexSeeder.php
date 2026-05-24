<?php

namespace Database\Seeders\Tenant\SceneFields;

use App\Enums\CouponDetailStatus;

class CouponDetailIndexSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page' => 'CouponDetailIndex',
                'name' => '卡券状态',
                'table' => 'coupon_details',
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
                    'options' => collect(CouponDetailStatus::options())
                        ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
                        ->values()
                        ->all(),
                ]),
            ],
            [
                'page' => 'CouponDetailIndex',
                'name' => '卡券名称',
                'table' => 'coupon_details',
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
                'page' => 'CouponDetailIndex',
                'name' => '卡券编号',
                'table' => 'coupon_details',
                'field' => 'number',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            ...$this->numberFields(),
            [
                'page' => 'CouponDetailIndex',
                'name' => '过期时间',
                'table' => 'coupon_details',
                'field' => 'expire_time',
                'field_type' => 'datetime',
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
                'page' => 'CouponDetailIndex',
                'name' => '备注',
                'table' => 'coupon_details',
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
                'page' => 'CouponDetailIndex',
                'name' => '发券人员',
                'table' => 'coupon_details',
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

    /**
     * 金额与比例类字段使用相同的数值筛选配置。
     */
    private function numberFields(): array
    {
        $operators = json_encode([
            ['text' => '等于', 'value' => '='],
            ['text' => '不等于', 'value' => '<>'],
            ['text' => '大于', 'value' => '>'],
            ['text' => '大于等于', 'value' => '>='],
            ['text' => '小于', 'value' => '<'],
            ['text' => '小于等于', 'value' => '<='],
            ['text' => '介于', 'value' => 'between'],
        ]);

        return collect([
            ['name' => '卡券面值', 'field' => 'coupon_value'],
            ['name' => '卡券余额', 'field' => 'balance'],
            ['name' => '支付金额', 'field' => 'sales_price'],
            ['name' => '充赠比例', 'field' => 'rate'],
            ['name' => '扣除积分', 'field' => 'integrals'],
        ])->map(fn (array $field): array => [
            'page' => 'CouponDetailIndex',
            'name' => $field['name'],
            'table' => 'coupon_details',
            'field' => $field['field'],
            'field_type' => 'decimal',
            'component' => 'numberbox',
            'operators' => $operators,
        ])->all();
    }
}
