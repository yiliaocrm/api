<?php

namespace Tests\Feature\Web;

use App\Http\Requests\Web\SceneRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SceneRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dropFixtureTables();
        $this->createFixtureTables();
        $this->seedFixtureTables();
    }

    protected function tearDown(): void
    {
        $this->dropFixtureTables();
        parent::tearDown();
    }

    public function test_formatter_text_formats_static_multi_select_equals_values(): void
    {
        $request = SceneRequest::create('/scene/format', 'POST', [
            'page' => 'ErkaiIndex',
        ]);

        $text = $request->formatterText([
            'field' => 'status',
            'operator' => '=',
            'value' => [1, 2],
        ]);

        $this->assertSame('状态 等于 待收费、已收费', $text);
    }

    public function test_formatter_text_formats_api_multi_select_not_equals_values(): void
    {
        $request = SceneRequest::create('/scene/format', 'POST', [
            'page' => 'ErkaiIndex',
        ]);

        $text = $request->formatterText([
            'field' => 'department_id',
            'operator' => '<>',
            'value' => [10, 20],
        ]);

        $this->assertSame('二开科室 不等于 市场部、咨询部', $text);
    }

    private function createFixtureTables(): void
    {
        Schema::create('scene_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('page')->nullable();
            $table->string('name')->nullable();
            $table->string('table')->nullable();
            $table->string('field')->nullable();
            $table->string('field_alias')->nullable();
            $table->string('field_type')->nullable();
            $table->string('component')->nullable();
            $table->string('api')->nullable();
            $table->text('operators')->nullable();
            $table->text('component_params')->nullable();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
        });
    }

    private function seedFixtureTables(): void
    {
        DB::table('scene_fields')->insert([
            [
                'page' => 'ErkaiIndex',
                'name' => '状态',
                'table' => 'erkai',
                'field' => 'status',
                'field_alias' => 'status',
                'field_type' => 'tinyint',
                'component' => 'select',
                'api' => null,
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ], JSON_THROW_ON_ERROR),
                'component_params' => json_encode([
                    'props' => [
                        'multiple' => true,
                    ],
                    'options' => [
                        ['label' => '待收费', 'value' => 1],
                        ['label' => '已收费', 'value' => 2],
                    ],
                ], JSON_THROW_ON_ERROR),
            ],
            [
                'page' => 'ErkaiIndex',
                'name' => '二开科室',
                'table' => 'erkai',
                'field' => 'department_id',
                'field_alias' => 'department_id',
                'field_type' => 'int',
                'component' => 'select',
                'api' => '/cache/departments',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ], JSON_THROW_ON_ERROR),
                'component_params' => json_encode([
                    'props' => [
                        'multiple' => true,
                    ],
                ], JSON_THROW_ON_ERROR),
            ],
        ]);

        DB::table('department')->insert([
            ['id' => 10, 'name' => '市场部'],
            ['id' => 20, 'name' => '咨询部'],
        ]);
    }

    private function dropFixtureTables(): void
    {
        Schema::dropIfExists('department');
        Schema::dropIfExists('scene_fields');
    }
}
