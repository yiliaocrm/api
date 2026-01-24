<?php

namespace Database\Seeders\Tenant\SceneFields;

class ReportRetailOutboundDetailSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            // 出料单号
            [
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '出料单号',
                'table'      => 'retail_outbound_detail',
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
            // 仓库
            [
                'page'             => 'ReportRetailOutboundDetail',
                'name'             => '出料仓库',
                'table'            => 'retail_outbound_detail',
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
            // 科室
            [
                'page'             => 'ReportRetailOutboundDetail',
                'name'             => '出料科室',
                'table'            => 'retail_outbound_detail',
                'field'            => 'department_id',
                'field_type'       => 'int',
                'component'        => 'select',
                'api'              => '/cache/departments',
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
            // 商品名称
            [
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '商品名称',
                'table'      => 'retail_outbound_detail',
                'field'      => 'goods_name',
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
            // 套餐名称
            [
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '套餐名称',
                'table'      => 'retail_outbound_detail',
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
            // 规格型号
            [
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '规格型号',
                'table'      => 'retail_outbound_detail',
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
            // 单位
            [
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '单位',
                'table'      => 'retail_outbound_detail',
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
            // 生产厂商
            [
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '生产厂商',
                'table'      => 'retail_outbound_detail',
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
                'page'             => 'ReportRetailOutboundDetail',
                'name'             => '生产日期',
                'table'            => 'retail_outbound_detail',
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
                'page'             => 'ReportRetailOutboundDetail',
                'name'             => '过期时间',
                'table'            => 'retail_outbound_detail',
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
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '批号',
                'table'      => 'retail_outbound_detail',
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
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => 'SN码',
                'table'      => 'retail_outbound_detail',
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
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '备注',
                'table'      => 'retail_outbound_detail',
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
            // 出料人员
            [
                'page'       => 'ReportRetailOutboundDetail',
                'name'       => '出料人员',
                'table'      => 'retail_outbound_detail',
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
