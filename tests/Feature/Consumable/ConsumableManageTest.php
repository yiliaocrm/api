<?php

namespace Tests\Feature\Consumable;

use App\Http\Controllers\Web\ConsumableController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsumableManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        Route::post('/consumable/manage', [ConsumableController::class, 'manage']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('inventory_batchs');
        Schema::dropIfExists('goods_unit');
        Schema::dropIfExists('consumable_detail');
        Schema::dropIfExists('consumable');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('users');
        Schema::dropIfExists('department');
        Schema::dropIfExists('warehouse');

        parent::tearDown();
    }

    public function test_manage_filters_by_new_date_range_keyword_and_scene_filters(): void
    {
        $this->seedSceneFields();
        $this->seedBaseRows();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date_start' => '2026-05-20',
            'date_end' => '2026-05-20',
            'keyword' => '目标顾客',
            'filters' => [
                [
                    'field' => 'warehouse_id',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame(1, $payload['data']['rows'][0]['id']);
        $this->assertSame('客户甲', $payload['data']['rows'][0]['customer']['name']);
        $this->assertSame('耗材A', $payload['data']['rows'][0]['details'][0]['goods_name']);
    }

    public function test_manage_keeps_legacy_date_and_customer_keyword_filters(): void
    {
        $this->seedSceneFields();
        $this->seedBaseRows();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date_at_start' => '2026-05-21',
            'date_at_end' => '2026-05-21',
            'customer_keyword' => '旧版顾客',
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame(2, $payload['data']['rows'][0]['id']);
        $this->assertSame('客户乙', $payload['data']['rows'][0]['customer']['name']);
    }

    private function dispatchManage(array $data): array
    {
        $request = Request::create(
            '/consumable/manage',
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

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (! array_key_exists('code', $payload)) {
            $this->fail($response->getContent());
        }

        return $payload;
    }

    private function seedSceneFields(): void
    {
        DB::table('scene_fields')->insert([
            [
                'page' => 'ConsumableIndex',
                'name' => '出库仓库',
                'table' => 'consumable',
                'field' => 'warehouse_id',
                'field_alias' => 'warehouse_id',
                'field_type' => 'int',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ], JSON_THROW_ON_ERROR),
                'query_config' => null,
                'keyword' => '出库仓库',
            ],
        ]);
    }

    private function seedBaseRows(): void
    {
        DB::table('warehouse')->insert([
            ['id' => 1, 'name' => '一号仓', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '二号仓', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('department')->insert([
            ['id' => 1, 'name' => '药房', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '皮肤科', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('users')->insert([
            ['id' => 11, 'name' => '领料员甲', 'email' => 'u11@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '录单员乙', 'email' => 'u12@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('customer')->insert([
            [
                'id' => 'customer-1',
                'name' => '客户甲',
                'idcard' => 'C001',
                'keyword' => '客户甲,目标顾客,13800138000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'customer-2',
                'name' => '客户乙',
                'idcard' => 'C002',
                'keyword' => '客户乙,旧版顾客,13900139000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('consumable')->insert([
            [
                'id' => 1,
                'key' => 'YLDJ202605200001',
                'date' => '2026-05-20',
                'customer_id' => 'customer-1',
                'warehouse_id' => 1,
                'department_id' => 1,
                'amount' => 20.0000,
                'customer_product_id' => 101,
                'product_id' => 201,
                'product_name' => '目标项目',
                'remark' => '目标单据',
                'user_id' => 11,
                'create_user_id' => 12,
                'created_at' => '2026-05-20 09:00:00',
                'updated_at' => '2026-05-20 09:00:00',
            ],
            [
                'id' => 2,
                'key' => 'YLDJ202605210001',
                'date' => '2026-05-21',
                'customer_id' => 'customer-2',
                'warehouse_id' => 2,
                'department_id' => 2,
                'amount' => 30.0000,
                'customer_product_id' => 102,
                'product_id' => 202,
                'product_name' => '旧版项目',
                'remark' => '旧版单据',
                'user_id' => 11,
                'create_user_id' => 12,
                'created_at' => '2026-05-21 10:00:00',
                'updated_at' => '2026-05-21 10:00:00',
            ],
        ]);
        DB::table('consumable_detail')->insert([
            [
                'id' => 101,
                'consumable_id' => 1,
                'key' => 'YLDJ202605200001',
                'date' => '2026-05-20',
                'customer_id' => 'customer-1',
                'warehouse_id' => 1,
                'department_id' => 1,
                'goods_id' => 501,
                'goods_name' => '耗材A',
                'specs' => '10ml',
                'inventory_batchs_id' => 9001,
                'batch_code' => 'BATCH-A',
                'unit_id' => 1,
                'unit_name' => '支',
                'price' => 20.0000,
                'number' => 1.0000,
                'amount' => 20.0000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 102,
                'consumable_id' => 2,
                'key' => 'YLDJ202605210001',
                'date' => '2026-05-21',
                'customer_id' => 'customer-2',
                'warehouse_id' => 2,
                'department_id' => 2,
                'goods_id' => 502,
                'goods_name' => '耗材B',
                'specs' => '20ml',
                'inventory_batchs_id' => 9002,
                'batch_code' => 'BATCH-B',
                'unit_id' => 1,
                'unit_name' => '支',
                'price' => 30.0000,
                'number' => 1.0000,
                'amount' => 30.0000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('goods_unit')->insert([
            ['id' => 1, 'goods_id' => 501, 'unit_id' => 1, 'basic' => 1, 'rate' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'goods_id' => 502, 'unit_id' => 1, 'basic' => 1, 'rate' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('inventory_batchs')->insert([
            [
                'id' => 9001,
                'warehouse_id' => 1,
                'goods_id' => 501,
                'goods_name' => '耗材A',
                'unit_id' => 1,
                'unit_name' => '支',
                'price' => 20.0000,
                'number' => 10.0000,
                'amount' => 200.0000,
                'batch_code' => 'BATCH-A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9002,
                'warehouse_id' => 2,
                'goods_id' => 502,
                'goods_name' => '耗材B',
                'unit_id' => 1,
                'unit_name' => '支',
                'price' => 30.0000,
                'number' => 10.0000,
                'amount' => 300.0000,
                'batch_code' => 'BATCH-B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function createTables(): void
    {
        Schema::create('scene_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('page')->nullable();
            $table->string('name')->nullable();
            $table->string('table')->nullable();
            $table->string('field')->nullable();
            $table->string('field_alias')->nullable();
            $table->string('field_type')->nullable();
            $table->text('operators')->nullable();
            $table->text('query_config')->nullable();
            $table->string('keyword')->nullable();
        });

        Schema::create('warehouse', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('consumable', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->date('date');
            $table->uuid('customer_id');
            $table->integer('warehouse_id');
            $table->integer('department_id');
            $table->decimal('amount', 14, 4)->default(0);
            $table->integer('customer_product_id');
            $table->integer('product_id');
            $table->string('product_name');
            $table->text('remark')->nullable();
            $table->integer('user_id');
            $table->integer('create_user_id');
            $table->timestamps();
        });

        Schema::create('consumable_detail', function (Blueprint $table): void {
            $table->id();
            $table->integer('consumable_id');
            $table->string('key');
            $table->date('date');
            $table->uuid('customer_id');
            $table->integer('warehouse_id');
            $table->integer('department_id');
            $table->integer('goods_id');
            $table->string('goods_name');
            $table->string('specs')->nullable();
            $table->integer('inventory_batchs_id');
            $table->string('batch_code');
            $table->integer('unit_id');
            $table->string('unit_name');
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('number', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('goods_unit', function (Blueprint $table): void {
            $table->id();
            $table->integer('goods_id');
            $table->integer('unit_id');
            $table->integer('basic')->default(0);
            $table->decimal('rate', 14, 4)->default(1);
            $table->timestamps();
        });

        Schema::create('inventory_batchs', function (Blueprint $table): void {
            $table->id();
            $table->integer('warehouse_id');
            $table->integer('goods_id');
            $table->string('goods_name');
            $table->integer('unit_id');
            $table->string('unit_name');
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('number', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->string('batch_code')->nullable();
            $table->timestamps();
        });
    }
}
