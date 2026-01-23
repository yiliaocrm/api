<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportInventoryDetailSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            // 单据编号
            [
                'page'       => 'ReportInventoryDetail',
                'name'       => '单据编号',
                'table'      => 'inventory_detail',
                'field'      => 'key',
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
            // 规格型号
            [
                'page'       => 'ReportInventoryDetail',
                'name'       => '规格型号',
                'table'      => 'inventory_detail',
                'field'      => 'specs',
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
            // 仓库名称
            [
                'page'             => 'ReportInventoryDetail',
                'name'             => '仓库名称',
                'table'            => 'inventory_detail',
                'field'            => 'warehouse_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/warehouse',
                'component_params' => json_encode([
                    'props' => [
                        'clearable'  => true,
                        'filterable' => true,
                        'multiple'   => true,
                    ],
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
            // 单位
            [
                'page'       => 'ReportInventoryDetail',
                'name'       => '单位',
                'table'      => 'inventory_detail',
                'field'      => 'unit_name',
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
            // 生产厂家
            [
                'page'       => 'ReportInventoryDetail',
                'name'       => '生产厂家',
                'table'      => 'inventory_detail',
                'field'      => 'manufacturer_name',
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
            // 生产日期
            [
                'page'             => 'ReportInventoryDetail',
                'name'             => '生产日期',
                'table'            => 'inventory_detail',
                'field'            => 'production_date',
                'field_type'       => 'date',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => ['type' => 'date']
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '区间', 'value' => 'between'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 过期时间
            [
                'page'             => 'ReportInventoryDetail',
                'name'             => '过期时间',
                'table'            => 'inventory_detail',
                'field'            => 'expiry_date',
                'field_type'       => 'date',
                'component'        => 'date-picker',
                'component_params' => json_encode([
                    'props' => ['type' => 'date']
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '大于', 'value' => '>'],
                    ['text' => '小于', 'value' => '<'],
                    ['text' => '区间', 'value' => 'between'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 批号
            [
                'page'       => 'ReportInventoryDetail',
                'name'       => '批号',
                'table'      => 'inventory_detail',
                'field'      => 'batch_code',
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
            // SN码
            [
                'page'       => 'ReportInventoryDetail',
                'name'       => 'SN码',
                'table'      => 'inventory_detail',
                'field'      => 'sncode',
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
            // 备注
            [
                'page'       => 'ReportInventoryDetail',
                'name'       => '备注',
                'table'      => 'inventory_detail',
                'field'      => 'remark',
                'field_type' => 'text',
                'component'  => 'input',
                'operators'  => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null']
                ])
            ],
            // 业务类型
            [
                'page'             => 'ReportInventoryDetail',
                'name'             => '业务类型',
                'table'            => 'inventory_detail',
                'field'            => 'detailable_type',
                'field_type'       => 'varchar',
                'component'        => 'select',
                'component_params' => json_encode([
                    'props'   => [
                        'multiple'  => true,
                        'clearable' => true,
                    ],
                    'options' => $this->convertSettingConfigToOptions('setting.inventory_detail.detailable_type')
                ]),
                'operators'        => json_encode([
                    ['text' => '等于', 'value' => 'in'],
                    ['text' => '不等于', 'value' => 'not in']
                ])
            ],
        ];
    }
}
