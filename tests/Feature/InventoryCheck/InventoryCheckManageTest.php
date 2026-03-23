<?php

namespace Tests\Feature\InventoryCheck;

use App\Http\Controllers\Web\InventoryCheckController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryCheckManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('inventory-check/manage', 'POST', InventoryCheckController::class.'@manage');
        Route::post('/inventory-check/manage', [InventoryCheckController::class, 'manage']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('inventory_batchs');
        Schema::dropIfExists('inventory_check_details');
        Schema::dropIfExists('inventory_checks');
        Schema::dropIfExists('inventory_overflows');
        Schema::dropIfExists('inventory_losses');
        Schema::dropIfExists('goods');
        Schema::dropIfExists('unit');
        Schema::dropIfExists('manufacturer');
        Schema::dropIfExists('users');
        Schema::dropIfExists('department');
        Schema::dropIfExists('warehouse');

        parent::tearDown();
    }

    public function test_manage_lists_inventory_checks_with_relations(): void
    {
        DB::table('warehouse')->insert([
            'id' => 1,
            'name' => '总仓',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('department')->insert([
            'id' => 1,
            'name' => '药房',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            ['id' => 11, 'name' => '经办人', 'email' => 'u11@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '审核人', 'email' => 'u12@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => '录单人', 'email' => 'u13@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('inventory_losses')->insert([
            'id' => 21,
            'key' => 'LS202603220001',
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'department_id' => 1,
            'user_id' => 11,
            'create_user_id' => 13,
            'status' => 1,
            'amount' => 10.0000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_overflows')->insert([
            'id' => 31,
            'key' => 'OF202603220001',
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'department_id' => 1,
            'user_id' => 11,
            'create_user_id' => 13,
            'status' => 1,
            'amount' => 8.0000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('manufacturer')->insert([
            'id' => 41,
            'name' => '生产厂家A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('unit')->insert([
            'id' => 42,
            'name' => '盒',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('goods')->insert([
            'id' => 51,
            'name' => '测试物品',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_batchs')->insert([
            'id' => 9001,
            'warehouse_id' => 1,
            'goods_id' => 51,
            'goods_name' => '测试物品',
            'specs' => '100mg*10',
            'manufacturer_id' => 41,
            'manufacturer_name' => '生产厂家A',
            'unit_id' => 42,
            'unit_name' => '盒',
            'price' => 20.0000,
            'number' => 10.0000,
            'amount' => 200.0000,
            'batch_code' => 'BATCH-51-001',
            'production_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'sncode' => 'SN-51-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_checks')->insert([
            'id' => 1,
            'key' => 'IC202603220001',
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'department_id' => 1,
            'user_id' => 11,
            'remark' => '盘点测试',
            'status' => 1,
            'check_user' => 12,
            'check_time' => '2026-03-22 10:00:00',
            'inventory_loss_id' => 21,
            'inventory_overflow_id' => 31,
            'create_user_id' => 13,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_check_details')->insert([
            'id' => 101,
            'inventory_check_id' => 1,
            'key' => 'IC202603220001',
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'goods_id' => 51,
            'goods_name' => '测试物品',
            'specs' => '100mg*10',
            'manufacturer_id' => 41,
            'manufacturer_name' => '生产厂家A',
            'inventory_batchs_id' => 9001,
            'batch_code' => 'BATCH-51-001',
            'production_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'sncode' => 'SN-51-001',
            'unit_id' => 42,
            'unit_name' => '盒',
            'book_number' => 10.0000,
            'actual_number' => 9.0000,
            'diff_number' => -1.0000,
            'price' => 20.0000,
            'diff_amount' => -20.0000,
            'remark' => '少1盒',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create(
            '/inventory-check/manage',
            'POST',
            [
                'rows' => 10,
                'sort' => 'inventory_checks.id',
                'order' => 'asc',
            ],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame(1, $payload['data']['rows'][0]['id']);
        $this->assertSame(1, $payload['data']['rows'][0]['warehouse']['id']);
        $this->assertSame(1, $payload['data']['rows'][0]['department']['id']);
        $this->assertSame(11, $payload['data']['rows'][0]['user']['id']);
        $this->assertSame(12, $payload['data']['rows'][0]['check_user']['id']);
        $this->assertSame(13, $payload['data']['rows'][0]['create_user']['id']);
        $this->assertSame(21, $payload['data']['rows'][0]['inventory_loss']['id']);
        $this->assertSame(31, $payload['data']['rows'][0]['inventory_overflow']['id']);
        $this->assertSame(101, $payload['data']['rows'][0]['details'][0]['id']);
        $this->assertSame(51, $payload['data']['rows'][0]['details'][0]['goods']['id']);
        $this->assertSame(9001, $payload['data']['rows'][0]['details'][0]['inventory_batchs_id']);
        $this->assertSame('BATCH-51-001', $payload['data']['rows'][0]['details'][0]['batch_code']);
        $this->assertSame('2026-01-01', $payload['data']['rows'][0]['details'][0]['production_date']);
        $this->assertSame('2027-01-01', $payload['data']['rows'][0]['details'][0]['expiry_date']);
        $this->assertSame('SN-51-001', $payload['data']['rows'][0]['details'][0]['sncode']);
        $this->assertSame(9001, $payload['data']['rows'][0]['details'][0]['inventory_batch']['id']);
        $this->assertSame('BATCH-51-001', $payload['data']['rows'][0]['details'][0]['inventory_batch']['batch_code']);
        $this->assertSame('SN-51-001', $payload['data']['rows'][0]['details'][0]['inventory_batch']['sncode']);
    }

    public function test_manage_filters_by_keyword_and_keeps_loaded_relations(): void
    {
        DB::table('warehouse')->insert([
            'id' => 1,
            'name' => '总仓',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('department')->insert([
            'id' => 1,
            'name' => '药房',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            ['id' => 11, 'name' => '经办人', 'email' => 'u11@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '审核人', 'email' => 'u12@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => '录单人', 'email' => 'u13@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('inventory_checks')->insert([
            [
                'id' => 1,
                'key' => 'IC202603220011',
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 11,
                'remark' => '有目标关键字',
                'status' => 1,
                'check_user' => 12,
                'check_time' => '2026-03-22 10:00:00',
                'inventory_loss_id' => null,
                'inventory_overflow_id' => null,
                'create_user_id' => 13,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'key' => 'IC202603220012',
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 11,
                'remark' => '无关键字',
                'status' => 1,
                'check_user' => 12,
                'check_time' => '2026-03-22 10:05:00',
                'inventory_loss_id' => null,
                'inventory_overflow_id' => null,
                'create_user_id' => 13,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('goods')->insert([
            ['id' => 51, 'name' => '感冒灵', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 52, 'name' => '维生素', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('inventory_check_details')->insert([
            [
                'id' => 111,
                'inventory_check_id' => 1,
                'key' => 'IC202603220011',
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'goods_id' => 51,
                'goods_name' => '感冒灵',
                'specs' => '10片',
                'manufacturer_id' => null,
                'manufacturer_name' => null,
                'inventory_batchs_id' => null,
                'batch_code' => 'BATCH-51-010',
                'production_date' => null,
                'expiry_date' => null,
                'sncode' => null,
                'unit_id' => null,
                'unit_name' => null,
                'book_number' => 10.0000,
                'actual_number' => 10.0000,
                'diff_number' => 0.0000,
                'price' => 5.0000,
                'diff_amount' => 0.0000,
                'remark' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 112,
                'inventory_check_id' => 2,
                'key' => 'IC202603220012',
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'goods_id' => 52,
                'goods_name' => '维生素',
                'specs' => '20片',
                'manufacturer_id' => null,
                'manufacturer_name' => null,
                'inventory_batchs_id' => null,
                'batch_code' => 'BATCH-52-020',
                'production_date' => null,
                'expiry_date' => null,
                'sncode' => null,
                'unit_id' => null,
                'unit_name' => null,
                'book_number' => 20.0000,
                'actual_number' => 20.0000,
                'diff_number' => 0.0000,
                'price' => 3.0000,
                'diff_amount' => 0.0000,
                'remark' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create(
            '/inventory-check/manage',
            'POST',
            [
                'rows' => 10,
                'sort' => 'inventory_checks.id',
                'order' => 'asc',
                'keyword' => '感冒',
            ],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame(1, $payload['data']['rows'][0]['id']);
        $this->assertSame('感冒灵', $payload['data']['rows'][0]['details'][0]['goods_name']);
        $this->assertSame(1, $payload['data']['rows'][0]['warehouse']['id']);
        $this->assertSame(11, $payload['data']['rows'][0]['user']['id']);
    }

    private function createTables(): void
    {
        Schema::create('scene_fields', function (Blueprint $table) {
            $table->id();
            $table->string('page')->nullable();
            $table->string('field')->nullable();
            $table->string('field_alias')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('department', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_losses', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->date('date');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('create_user_id');
            $table->tinyInteger('status')->default(1);
            $table->decimal('amount', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_overflows', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->date('date');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('create_user_id');
            $table->tinyInteger('status')->default(1);
            $table->decimal('amount', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('manufacturer', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('unit', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('goods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_batchs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('goods_id');
            $table->string('goods_name')->nullable();
            $table->string('specs')->nullable();
            $table->unsignedBigInteger('manufacturer_id')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('unit_name')->nullable();
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('number', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->string('batch_code')->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('sncode')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_checks', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->date('date');
            $table->integer('warehouse_id');
            $table->integer('department_id');
            $table->integer('user_id');
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->integer('check_user')->nullable();
            $table->dateTime('check_time')->nullable();
            $table->integer('inventory_loss_id')->nullable();
            $table->integer('inventory_overflow_id')->nullable();
            $table->integer('create_user_id');
            $table->timestamps();
        });

        Schema::create('inventory_check_details', function (Blueprint $table) {
            $table->id();
            $table->integer('inventory_check_id');
            $table->string('key');
            $table->date('date');
            $table->integer('warehouse_id');
            $table->integer('goods_id');
            $table->string('goods_name');
            $table->string('specs')->nullable();
            $table->integer('manufacturer_id')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->integer('inventory_batchs_id')->nullable();
            $table->string('batch_code')->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('sncode')->nullable();
            $table->integer('unit_id')->nullable();
            $table->string('unit_name')->nullable();
            $table->decimal('book_number', 14, 4)->default(0);
            $table->decimal('actual_number', 14, 4)->default(0);
            $table->decimal('diff_number', 14, 4)->default(0);
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('diff_amount', 14, 4)->default(0);
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    private function assertApplicationRouteExists(string $uri, string $method, string $action): void
    {
        if (! $this->hasRoute($uri, $method, $action)) {
            require base_path('routes/web.php');
        }

        $this->assertTrue(
            $this->hasRoute($uri, $method, $action),
            sprintf('Missing app route [%s %s] => %s from routes/web.php', $method, $uri, $action)
        );
    }

    private function hasRoute(string $uri, string $method, string $action): bool
    {
        foreach (app('router')->getRoutes() as $route) {
            $routeAction = $route->getAction()['controller'] ?? $route->getActionName();
            if (
                $route->uri() === $uri
                && in_array($method, $route->methods(), true)
                && $routeAction === $action
            ) {
                return true;
            }
        }

        return false;
    }
}
