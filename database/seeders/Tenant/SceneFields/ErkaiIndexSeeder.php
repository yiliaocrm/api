<?php

namespace Database\Seeders\Tenant\SceneFields;

use App\Enums\ErkaiStatus;

class ErkaiIndexSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page' => 'ErkaiIndex',
                'name' => '状态',
                'table' => 'erkai',
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
                        'multiple' => true,
                    ],
                    'options' => collect(ErkaiStatus::options())
                        ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
                        ->values()
                        ->all(),
                ]),
            ],
            [
                'page' => 'ErkaiIndex',
                'name' => '二开科室',
                'table' => 'erkai',
                'field' => 'department_id',
                'field_type' => 'int',
                'component' => 'select',
                'api' => '/cache/departments',
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                        'filterable' => true,
                        'multiple' => true,
                    ],
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'ErkaiIndex',
                'name' => '应收金额',
                'table' => 'erkai',
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
                'page' => 'ErkaiIndex',
                'name' => '实收金额',
                'table' => 'erkai',
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
                'page' => 'ErkaiIndex',
                'name' => '余额支付',
                'table' => 'erkai',
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
                'page' => 'ErkaiIndex',
                'name' => '券支付',
                'table' => 'erkai',
                'field' => 'coupon',
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
                'page' => 'ErkaiIndex',
                'name' => '欠费金额',
                'table' => 'erkai',
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
                'page' => 'ErkaiIndex',
                'name' => '录单人员',
                'table' => 'erkai',
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
                'page' => 'ErkaiIndex',
                'name' => '备注信息',
                'table' => 'erkai',
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
