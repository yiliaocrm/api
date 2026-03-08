<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ParseWorkflowConditionField;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParseWorkflowConditionFieldTest extends TestCase
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

    public function test_evaluate_group_matches_simple_equals(): void
    {
        $this->seedProduct(1, 'Product A', 100, 1, 1);

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 1],
            ],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertTrue($parser->evaluateGroup($group, [], []));
    }

    public function test_evaluate_group_returns_false_when_no_match(): void
    {
        $this->seedProduct(1, 'Product A', 100, 1, 1);

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 999],
            ],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertFalse($parser->evaluateGroup($group, [], []));
    }

    public function test_evaluate_group_resolves_context_value(): void
    {
        $this->seedProduct(1, 'Product A', 100, 1, 1);

        $context = [
            'payload' => ['product_id' => 1],
        ];

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => '{{ payload.product_id }}'],
            ],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertTrue($parser->evaluateGroup($group, $context, []));
    }

    public function test_evaluate_group_like_operator(): void
    {
        $this->seedProduct(1, '注射美白针', 100, 1, 1);

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'name', 'operator' => 'like', 'value' => '美白'],
            ],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertTrue($parser->evaluateGroup($group, [], []));
    }

    public function test_evaluate_group_comparison_operators(): void
    {
        $this->seedProduct(1, 'Product A', 150.50, 1, 1);

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'price', 'operator' => '>', 'value' => 100],
            ],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertTrue($parser->evaluateGroup($group, [], []));

        $group['rules'][0]['operator'] = '<';
        $this->assertFalse($parser->evaluateGroup($group, [], []));
    }

    public function test_evaluate_group_match_type_any(): void
    {
        $this->seedProduct(1, 'Product A', 100, 1, 1);

        $group = [
            'matchType' => 'any',
            'rules' => [
                ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 999],
                ['table' => 'product', 'field' => 'name', 'operator' => '=', 'value' => 'Product A'],
            ],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertTrue($parser->evaluateGroup($group, [], []));
    }

    public function test_evaluate_group_match_type_all_fails_when_one_misses(): void
    {
        $this->seedProduct(1, 'Product A', 100, 1, 1);

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 1],
                ['table' => 'product', 'field' => 'name', 'operator' => '=', 'value' => 'Wrong Name'],
            ],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertFalse($parser->evaluateGroup($group, [], []));
    }

    public function test_evaluate_group_empty_rules_returns_false(): void
    {
        $group = [
            'matchType' => 'all',
            'rules' => [],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertFalse($parser->evaluateGroup($group, [], []));
    }

    public function test_evaluate_group_cross_table_with_match_type_all(): void
    {
        $this->seedProduct(1, 'Product A', 100, 1, 1);
        DB::table('treatment')->insert([
            'id' => 1, 'product_id' => 1, 'department_id' => 1, 'times' => 3, 'price' => 100,
        ]);

        $group = [
            'matchType' => 'all',
            'rules' => [
                ['table' => 'product', 'field' => 'id', 'operator' => '=', 'value' => 1],
                ['table' => 'treatment', 'field' => 'product_id', 'operator' => '=', 'value' => 1],
            ],
        ];

        $parser = new ParseWorkflowConditionField;
        $this->assertTrue($parser->evaluateGroup($group, [], []));
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

        // Seed field config
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
