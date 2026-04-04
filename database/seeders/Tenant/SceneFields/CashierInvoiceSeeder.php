<?php

namespace Database\Seeders\Tenant\SceneFields;

use App\Enums\CashierInvoiceType;

class CashierInvoiceSeeder extends BaseSceneFieldSeeder
{
    public function getConfig(): array
    {
        return [
            [
                'page' => 'CashierInvoiceIndex',
                'name' => '开票类型',
                'table' => 'cashier_invoices',
                'field' => 'type',
                'field_type' => 'varchar',
                'component' => 'select',
                'component_params' => json_encode([
                    'props' => [
                        'clearable' => true,
                        'filterable' => true,
                    ],
                    'options' => collect(CashierInvoiceType::options())
                        ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
                        ->values()
                        ->all(),
                ]),
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CashierInvoiceIndex',
                'name' => '开票单号',
                'table' => 'cashier_invoices',
                'field' => 'key',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'page' => 'CashierInvoiceIndex',
                'name' => '开票日期',
                'table' => 'cashier_invoices',
                'field' => 'date',
                'field_type' => 'date',
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
                'page' => 'CashierInvoiceIndex',
                'name' => '税号',
                'table' => 'cashier_invoices',
                'field' => 'tax_number',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CashierInvoiceIndex',
                'name' => '发票代码',
                'table' => 'cashier_invoices',
                'field' => 'code',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CashierInvoiceIndex',
                'name' => '发票号码',
                'table' => 'cashier_invoices',
                'field' => 'number',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CashierInvoiceIndex',
                'name' => '抬头',
                'table' => 'cashier_invoices',
                'field' => 'title',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'page' => 'CashierInvoiceIndex',
                'name' => '开票金额',
                'table' => 'cashier_invoices',
                'field' => 'amount',
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
                'page' => 'CashierInvoiceIndex',
                'name' => '开票人员',
                'table' => 'cashier_invoices',
                'field' => 'create_user_id',
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
                'page' => 'CashierInvoiceIndex',
                'name' => '备注',
                'table' => 'cashier_invoices',
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
                'page' => 'CashierInvoiceIndex',
                'name' => '创建时间',
                'table' => 'cashier_invoices',
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
                'page' => 'CashierInvoiceIndex',
                'name' => '更新时间',
                'table' => 'cashier_invoices',
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
