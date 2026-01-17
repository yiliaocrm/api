<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportCustomerGoodsSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'             => 'ReportCustomerGoods',
                'name'             => '收费日期',
                'table'            => 'customer_goods',
                'field'            => 'created_at',
                'field_type'       => 'timestamp',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => [
                        'type'         => 'date',
                        'value-format' => 'YYYY-MM-DD'
                    ]
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '大于等于', 'value' => '>='],
                    ['text' => '小于等于', 'value' => '<='],
                    ['text' => '区间', 'value' => 'between']
                ])
            ],
            [
                'page'       => 'ReportCustomerGoods',
                'name'       => '物品名称',
                'table'      => 'customer_goods',
                'field'      => 'goods_name',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ])
            ],
            [
                'page'       => 'ReportCustomerGoods',
                'name'       => '套餐名称',
                'table'      => 'customer_goods',
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
                'page'             => 'ReportCustomerGoods',
                'name'             => '单据状态',
                'table'            => 'customer_goods',
                'field'            => 'status',
                'field_type'       => 'tinyint',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'clearable' => true
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.customer_goods.status')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>']
                ])
            ],
        ];
    }
}
