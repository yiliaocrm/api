<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportCashierListSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page'       => 'ReportCashierList',
                'name'       => '录单人员',
                'table'      => 'cashier',
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
            [
                'page'       => 'ReportCashierList',
                'name'       => '单据编号',
                'table'      => 'cashier',
                'field'      => 'key',
                'field_type' => 'varchar',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page'       => 'ReportCashierList',
                'name'       => '收费人员',
                'table'      => 'cashier',
                'field'      => 'operator',
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
                'page'             => 'ReportCashierList',
                'name'             => '业务类型',
                'table'            => 'cashier',
                'field'            => 'cashierable_type',
                'field_type'       => 'varchar',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'  => true,
                        'clearable' => true,
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.cashier.cashierable_type')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
        ];
    }
}
