<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowConditionFieldsTableSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            [
                'table' => 'product',
                'field' => 'id',
                'field_type' => 'int',
                'table_name' => '收费项目',
                'field_name' => '项目ID',
                'component' => 'input',
                'context_binding' => '{{ payload.product_id }}',
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'table' => 'product',
                'field' => 'name',
                'field_type' => 'varchar',
                'table_name' => '收费项目',
                'field_name' => '项目名称',
                'component' => 'input',
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '包含', 'value' => 'like'],
                ]),
            ],
            [
                'table' => 'product',
                'field' => 'type_id',
                'field_type' => 'int',
                'table_name' => '收费项目',
                'field_name' => '项目分类',
                'api' => '/cache/product-type?cascader=1',
                'component' => 'cascader',
                'component_params' => json_encode([
                    'props' => [
                        'props' => [
                            'label' => 'name',
                            'value' => 'id',
                            'checkStrictly' => true,
                        ],
                        'clearable' => true,
                        'filterable' => true,
                    ],
                ]),
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ]),
                'query_config' => json_encode([
                    [
                        'operator' => '=',
                        'joins' => [
                            [
                                'table' => 'product_type',
                                'first' => 'product_type.id',
                                'operator' => '=',
                                'second' => 'product.type_id',
                                'type' => 'left',
                            ],
                        ],
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "(cy_product_type.tree LIKE CONCAT((SELECT tree FROM cy_product_type WHERE id = ?), '-%') OR cy_product_type.id = ?)",
                                'bindings' => ['{$value[-1]}', '{$value[-1]}'],
                            ],
                        ],
                    ],
                    [
                        'operator' => '<>',
                        'joins' => [
                            [
                                'table' => 'product_type',
                                'first' => 'product_type.id',
                                'operator' => '=',
                                'second' => 'product.type_id',
                                'type' => 'left',
                            ],
                        ],
                        'wheres' => [
                            [
                                'type' => 'whereRaw',
                                'sql' => "cy_product_type.tree NOT LIKE CONCAT((SELECT tree FROM cy_product_type WHERE id = ?), '-%') AND cy_product_type.id <> ?",
                                'bindings' => ['{$value[-1]}', '{$value[-1]}'],
                            ],
                        ],
                    ],
                ]),
            ],
            [
                'table' => 'product',
                'field' => 'price',
                'field_type' => 'decimal',
                'table_name' => '收费项目',
                'field_name' => '项目价格',
                'component' => 'input-number',
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                ]),
            ],
            [
                'table' => 'product',
                'field' => 'department_id',
                'field_type' => 'int',
                'table_name' => '收费项目',
                'field_name' => '所属科室',
                'api' => '/cache/departments',
                'component' => 'select',
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'table' => 'treatment',
                'field' => 'product_id',
                'field_type' => 'int',
                'table_name' => '治疗记录',
                'field_name' => '项目ID',
                'component' => 'input',
                'context_binding' => '{{ payload.product_id }}',
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'table' => 'treatment',
                'field' => 'department_id',
                'field_type' => 'int',
                'table_name' => '治疗记录',
                'field_name' => '执行科室',
                'api' => '/cache/departments',
                'component' => 'select',
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                ]),
            ],
            [
                'table' => 'treatment',
                'field' => 'times',
                'field_type' => 'int',
                'table_name' => '治疗记录',
                'field_name' => '划扣次数',
                'component' => 'input-number',
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                ]),
            ],
            [
                'table' => 'treatment',
                'field' => 'price',
                'field_type' => 'decimal',
                'table_name' => '治疗记录',
                'field_name' => '划扣价格',
                'component' => 'input-number',
                'operators' => json_encode([
                    ['label' => '等于', 'value' => '='],
                    ['label' => '不等于', 'value' => '<>'],
                    ['label' => '大于', 'value' => '>'],
                    ['label' => '大于等于', 'value' => '>='],
                    ['label' => '小于', 'value' => '<'],
                    ['label' => '小于等于', 'value' => '<='],
                    ['label' => '在区间', 'value' => 'between'],
                    ['label' => '不在区间', 'value' => 'not between'],
                ]),
            ],
        ];

        foreach ($fields as &$field) {
            $field['api'] = $field['api'] ?? null;
            $field['keyword'] = implode(',', parse_pinyin($field['table_name'].$field['field_name']));
            $field['auto_join'] = $field['auto_join'] ?? 0;
            $field['query_config'] = $field['query_config'] ?? null;
            $field['component_params'] = $field['component_params'] ?? null;
            $field['context_binding'] = $field['context_binding'] ?? null;
        }

        DB::table('workflow_condition_fields')->truncate();
        DB::table('workflow_condition_fields')->insert($fields);
    }
}
