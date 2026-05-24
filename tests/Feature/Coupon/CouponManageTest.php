<?php

namespace Tests\Feature\Coupon;

use App\Http\Controllers\Web\CouponController;
use Database\Seeders\Tenant\SceneFields\CouponIndexSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CouponManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        Route::post('/coupon/manage', [CouponController::class, 'manage']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('users');
        Schema::dropIfExists('coupons');

        parent::tearDown();
    }

    public function test_manage_filters_by_status_and_scene_filters(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCoupons();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'status' => 1,
            'filters' => [
                [
                    'field' => 'created_at',
                    'operator' => 'between',
                    'value' => ['2026-01-01', '2026-12-31'],
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('目标卡券', $payload['data']['rows'][0]['name']);
        $this->assertSame(1, $payload['data']['rows'][0]['status']);
    }

    public function test_coupon_index_scene_field_seeder_contains_expected_fields(): void
    {
        $config = (new CouponIndexSeeder)->getConfig();
        $fields = array_column($config, 'field');

        $this->assertNotContains('name', $fields);
        $this->assertContains('status', $fields);
        $this->assertContains('created_at', $fields);
        $this->assertContains('create_user_id', $fields);
    }

    private function dispatchManage(array $data): array
    {
        $request = Request::create(
            '/coupon/manage',
            'POST',
            $data,
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        app()->instance('request', $request);

        $response = app('router')->dispatch($request);

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function seedSceneFields(): void
    {
        $sceneFields = array_map(
            static function (array $field): array {
                $field['api'] = $field['api'] ?? null;
                $field['field_alias'] = $field['field_alias'] ?? null;
                $field['query_config'] = $field['query_config'] ?? null;
                $field['component_params'] = $field['component_params'] ?? null;
                $field['keyword'] = implode(',', parse_pinyin($field['name']));

                return $field;
            },
            (new CouponIndexSeeder)->getConfig()
        );

        DB::table('scene_fields')->insert($sceneFields);
    }

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => '创建人',
            ],
        ]);
    }

    private function seedCoupons(): void
    {
        DB::table('coupons')->insert([
            [
                'id' => 1,
                'status' => 1,
                'name' => '目标卡券',
                'coupon_value' => 100,
                'least_consume' => 0,
                'total' => 10,
                'issue_count' => 0,
                'consume_count' => 0,
                'quota' => 1,
                'start' => '2026-01-01 00:00:00',
                'end' => '2026-12-31 23:59:59',
                'multiple_use' => 1,
                'sales_price' => 0,
                'integrals' => 0,
                'description' => null,
                'rate' => 0,
                'remark' => null,
                'create_user_id' => 1,
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ],
            [
                'id' => 2,
                'status' => 2,
                'name' => '目标卡券下架',
                'coupon_value' => 100,
                'least_consume' => 0,
                'total' => 10,
                'issue_count' => 0,
                'consume_count' => 0,
                'quota' => 1,
                'start' => '2026-01-01 00:00:00',
                'end' => '2026-12-31 23:59:59',
                'multiple_use' => 1,
                'sales_price' => 0,
                'integrals' => 0,
                'description' => null,
                'rate' => 0,
                'remark' => null,
                'create_user_id' => 1,
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ],
            [
                'id' => 3,
                'status' => 1,
                'name' => '其他卡券',
                'coupon_value' => 100,
                'least_consume' => 0,
                'total' => 10,
                'issue_count' => 0,
                'consume_count' => 0,
                'quota' => 1,
                'start' => '2026-01-01 00:00:00',
                'end' => '2026-12-31 23:59:59',
                'multiple_use' => 1,
                'sales_price' => 0,
                'integrals' => 0,
                'description' => null,
                'rate' => 0,
                'remark' => null,
                'create_user_id' => 1,
                'created_at' => '2025-05-01 10:00:00',
                'updated_at' => '2025-05-01 10:00:00',
            ],
        ]);
    }

    private function createTables(): void
    {
        Schema::create('scene_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('page')->nullable();
            $table->string('name')->nullable();
            $table->string('api')->nullable();
            $table->string('table')->nullable();
            $table->string('field')->nullable();
            $table->string('field_alias')->nullable();
            $table->string('field_type')->nullable();
            $table->string('component')->nullable();
            $table->text('component_params')->nullable();
            $table->text('operators')->nullable();
            $table->text('query_config')->nullable();
            $table->string('keyword')->nullable();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('status');
            $table->string('name', 100);
            $table->decimal('coupon_value', 14, 4);
            $table->decimal('least_consume', 14, 4)->nullable();
            $table->integer('total');
            $table->integer('issue_count')->default(0);
            $table->integer('consume_count')->default(0);
            $table->integer('quota')->nullable();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->tinyInteger('multiple_use');
            $table->decimal('sales_price', 14, 4);
            $table->decimal('integrals', 14, 4);
            $table->string('description')->nullable();
            $table->decimal('rate', 14, 4);
            $table->text('remark')->nullable();
            $table->integer('create_user_id');
            $table->timestamps();
        });
    }
}
