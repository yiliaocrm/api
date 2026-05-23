<?php

namespace Tests\Feature\Coupon;

use App\Http\Controllers\Web\CashierCouponController;
use Database\Seeders\Tenant\SceneFields\CouponCashierIndexSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierCouponIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->withoutExceptionHandling();
        Route::get('/cashier-coupon/index', [CashierCouponController::class, 'index']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('cashier_coupons');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    public function test_index_filters_by_date_keyword_and_scene_filters(): void
    {
        $this->seedSceneFields();
        $this->seedCustomers();
        $this->seedCashierCoupons();

        $payload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'sort' => 'created_at',
            'date' => ['2026-01-01', '2026-12-31'],
            'keyword' => '目标顾客',
            'filters' => [
                [
                    'field' => 'coupon_name',
                    'operator' => 'like',
                    'value' => '目标卡券',
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('cashier-coupon-1', $payload['data']['rows'][0]['id']);
        $this->assertSame('目标卡券', $payload['data']['rows'][0]['coupon_name']);
        $this->assertSame('目标顾客', $payload['data']['rows'][0]['customer']['name']);
    }

    public function test_coupon_cashier_scene_field_seeder_contains_only_table_filter_fields(): void
    {
        $config = (new CouponCashierIndexSeeder)->getConfig();
        $fields = array_column($config, 'field');

        $this->assertSame(
            ['cashier_id', 'coupon_number', 'coupon_name', 'income', 'remark'],
            $fields
        );
    }

    public function test_index_ignores_legacy_coupon_number_parameter(): void
    {
        $this->seedSceneFields();
        $this->seedCustomers();
        $this->seedCashierCoupons();

        $payload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-01-01', '2026-12-31'],
            'keyword' => '目标顾客',
            'coupon_number' => 'CP-002',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['total']);
    }

    private function dispatchIndex(array $data): array
    {
        $request = Request::create(
            '/cashier-coupon/index',
            'GET',
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
            (new CouponCashierIndexSeeder)->getConfig()
        );

        DB::table('scene_fields')->insert($sceneFields);
    }

    private function seedCustomers(): void
    {
        DB::table('customer')->insert([
            [
                'id' => 'customer-1',
                'name' => '目标顾客',
                'idcard' => 'CARD-001',
                'keyword' => '目标顾客,CARD-001,13800000000',
                'created_at' => '2026-01-01 10:00:00',
                'updated_at' => '2026-01-01 10:00:00',
            ],
            [
                'id' => 'customer-2',
                'name' => '其他顾客',
                'idcard' => 'CARD-002',
                'keyword' => '其他顾客,CARD-002,13900000000',
                'created_at' => '2026-01-01 10:00:00',
                'updated_at' => '2026-01-01 10:00:00',
            ],
        ]);
    }

    private function seedCashierCoupons(): void
    {
        DB::table('cashier_coupons')->insert([
            [
                'id' => 'cashier-coupon-1',
                'cashier_id' => 'cashier-1',
                'coupon_id' => 1,
                'coupon_detail_id' => 1,
                'coupon_name' => '目标卡券',
                'coupon_number' => 'CP-001',
                'customer_id' => 'customer-1',
                'income' => 80.0000,
                'remark' => '目标备注',
                'user_id' => 1,
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ],
            [
                'id' => 'cashier-coupon-2',
                'cashier_id' => 'cashier-2',
                'coupon_id' => 2,
                'coupon_detail_id' => 2,
                'coupon_name' => '其他卡券',
                'coupon_number' => 'CP-002',
                'customer_id' => 'customer-1',
                'income' => 90.0000,
                'remark' => '其他备注',
                'user_id' => 1,
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ],
            [
                'id' => 'cashier-coupon-3',
                'cashier_id' => 'cashier-3',
                'coupon_id' => 3,
                'coupon_detail_id' => 3,
                'coupon_name' => '目标卡券',
                'coupon_number' => 'CP-003',
                'customer_id' => 'customer-2',
                'income' => 100.0000,
                'remark' => '日期外',
                'user_id' => 1,
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

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('cashier_coupons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('cashier_id')->index();
            $table->integer('coupon_id')->unsigned();
            $table->integer('coupon_detail_id')->unsigned();
            $table->string('coupon_name');
            $table->string('coupon_number');
            $table->uuid('customer_id')->index();
            $table->decimal('income', 14, 4);
            $table->string('remark')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });
    }
}
