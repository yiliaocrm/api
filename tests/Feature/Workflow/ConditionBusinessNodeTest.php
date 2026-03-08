<?php

namespace Tests\Feature\Workflow;

use App\Helpers\ParseWorkflowConditionField;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConditionBusinessNodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product');
        Schema::dropIfExists('product_type');
        Schema::dropIfExists('treatment');
        Schema::dropIfExists('workflow_condition_fields');

        parent::tearDown();
    }

    public function test_first_matching_group_wins(): void
    {
        $this->seedProduct(1, 'Injection', 200, 1, 1);
        $this->seedProduct(2, 'Massage', 100, 2, 1);

        $parser = new ParseWorkflowConditionField;

        // group 0: product.id = 1 -> should match
        // group 1: product.id = 2 -> should also match, but group 0 wins
        $groups = [
            [
                'matchType' => 'all',
                'rules' => [
                    ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 1],
                ],
            ],
            [
                'matchType' => 'all',
                'rules' => [
                    ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 2],
                ],
            ],
        ];

        $matchedIndex = null;
        foreach ($groups as $index => $group) {
            if ($parser->evaluateGroup($group, [], [])) {
                $matchedIndex = $index;
                break;
            }
        }

        $this->assertSame(0, $matchedIndex);
    }

    public function test_default_branch_when_no_group_matches(): void
    {
        $this->seedProduct(1, 'Product A', 100, 1, 1);

        $parser = new ParseWorkflowConditionField;

        $groups = [
            [
                'matchType' => 'all',
                'rules' => [
                    ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 999],
                ],
            ],
        ];

        $matchedIndex = null;
        foreach ($groups as $index => $group) {
            if ($parser->evaluateGroup($group, [], [])) {
                $matchedIndex = $index;
                break;
            }
        }

        $this->assertNull($matchedIndex);
    }

    public function test_context_value_resolution_in_condition(): void
    {
        $this->seedProduct(5, 'Product Context', 300, 1, 1);

        $parser = new ParseWorkflowConditionField;

        $context = [
            'payload' => ['product_id' => 5],
        ];

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => '{{ payload.product_id }}'],
            ],
        ];

        $this->assertTrue($parser->evaluateGroup($group, $context, []));
    }

    public function test_empty_rules_group_does_not_match(): void
    {
        $parser = new ParseWorkflowConditionField;

        $group = [
            'matchType' => 'all',
            'rules' => [],
        ];

        $this->assertFalse($parser->evaluateGroup($group, [], []));
    }

    public function test_multiple_rules_same_table_combined(): void
    {
        $this->seedProduct(1, 'Product A', 200, 1, 1);

        $parser = new ParseWorkflowConditionField;

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 1],
                ['table' => 'product', 'field' => 'price', 'operator' => '>', 'value' => 150],
            ],
        ];

        $this->assertTrue($parser->evaluateGroup($group, [], []));

        // Change price threshold so it fails
        $group['rules'][1]['value'] = 300;
        $this->assertFalse($parser->evaluateGroup($group, [], []));
    }

    private function seedProduct(int $id, string $name, float $price, int $typeId, int $departmentId): void
    {
        DB::table('product')->insert([
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'type_id' => $typeId,
            'department_id' => $departmentId,
        ]);
    }

    private function createTables(): void
    {
        Schema::create('workflow_condition_fields', function (Blueprint $table) {
            $table->id();
            $table->string('table');
            $table->string('field');
            $table->string('field_type');
            $table->string('table_name');
            $table->string('field_name');
            $table->tinyInteger('auto_join')->default(0);
            $table->text('query_config')->nullable();
            $table->string('keyword')->nullable();
            $table->string('api')->nullable();
            $table->string('component');
            $table->text('component_params')->nullable();
            $table->text('operators');
            $table->string('context_binding')->nullable();
        });

        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->unsignedBigInteger('type_id');
            $table->unsignedBigInteger('department_id');
        });

        Schema::create('product_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tree')->nullable();
        });

        Schema::create('treatment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('department_id');
            $table->integer('times');
            $table->decimal('price', 10, 2);
        });

        $fields = [
            ['table' => 'product', 'field' => 'id', 'field_type' => 'int', 'table_name' => '收费项目', 'field_name' => '项目ID', 'component' => 'input', 'operators' => json_encode([['label' => '等于', 'value' => '=']]), 'context_binding' => '{{ payload.product_id }}'],
            ['table' => 'product', 'field' => 'name', 'field_type' => 'varchar', 'table_name' => '收费项目', 'field_name' => '项目名称', 'component' => 'input', 'operators' => json_encode([['label' => '等于', 'value' => '='], ['label' => '包含', 'value' => 'like']])],
            ['table' => 'product', 'field' => 'price', 'field_type' => 'decimal', 'table_name' => '收费项目', 'field_name' => '项目价格', 'component' => 'input-number', 'operators' => json_encode([['label' => '等于', 'value' => '='], ['label' => '大于', 'value' => '>']])],
            ['table' => 'product', 'field' => 'type_id', 'field_type' => 'int', 'table_name' => '收费项目', 'field_name' => '项目分类', 'component' => 'cascader', 'operators' => json_encode([['label' => '等于', 'value' => '=']])],
            ['table' => 'treatment', 'field' => 'product_id', 'field_type' => 'int', 'table_name' => '治疗记录', 'field_name' => '项目ID', 'component' => 'input', 'operators' => json_encode([['label' => '等于', 'value' => '=']]), 'context_binding' => '{{ payload.product_id }}'],
            ['table' => 'treatment', 'field' => 'department_id', 'field_type' => 'int', 'table_name' => '治疗记录', 'field_name' => '执行科室', 'component' => 'select', 'operators' => json_encode([['label' => '等于', 'value' => '=']])],
            ['table' => 'treatment', 'field' => 'times', 'field_type' => 'int', 'table_name' => '治疗记录', 'field_name' => '划扣次数', 'component' => 'input-number', 'operators' => json_encode([['label' => '等于', 'value' => '=']])],
            ['table' => 'treatment', 'field' => 'price', 'field_type' => 'decimal', 'table_name' => '治疗记录', 'field_name' => '划扣价格', 'component' => 'input-number', 'operators' => json_encode([['label' => '等于', 'value' => '=']])],
        ];

        foreach ($fields as &$field) {
            $field['auto_join'] = $field['auto_join'] ?? 0;
            $field['query_config'] = $field['query_config'] ?? null;
            $field['keyword'] = $field['keyword'] ?? null;
            $field['api'] = $field['api'] ?? null;
            $field['component_params'] = $field['component_params'] ?? null;
            $field['context_binding'] = $field['context_binding'] ?? null;
        }

        DB::table('workflow_condition_fields')->insert($fields);
    }
}
