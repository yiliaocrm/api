<?php

namespace Tests\Feature\InventoryCheck;

use App\Http\Controllers\Web\InventoryCheckController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryCheckCrudTest extends TestCase
{
    private int $authUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('inventory-check/create', 'POST', InventoryCheckController::class.'@create');
        $this->assertApplicationRouteExists('inventory-check/update', 'POST', InventoryCheckController::class.'@update');
        $this->assertApplicationRouteExists('inventory-check/remove', 'GET', InventoryCheckController::class.'@remove');
        Route::post('/inventory-check/create', [InventoryCheckController::class, 'create']);
        Route::post('/inventory-check/update', [InventoryCheckController::class, 'update']);
        Route::get('/inventory-check/remove', [InventoryCheckController::class, 'remove']);
        $this->createTables();
        $this->mockAuthUser();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_batchs');
        Schema::dropIfExists('inventory_check_details');
        Schema::dropIfExists('inventory_checks');
        Schema::dropIfExists('goods');
        Schema::dropIfExists('unit');
        Schema::dropIfExists('manufacturer');
        Schema::dropIfExists('users');
        Schema::dropIfExists('department');
        Schema::dropIfExists('warehouse');

        parent::tearDown();
    }

    public function test_create_inventory_check_draft(): void
    {
        $payload = [
            'form' => [
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 11,
                'remark' => '新建草稿',
            ],
            'detail' => [
                [
                    'goods_id' => 101,
                    'goods_name' => '测试商品A',
                    'specs' => '10片',
                    'manufacturer_id' => 301,
                    'manufacturer_name' => '厂家A',
                    'inventory_batchs_id' => 7001,
                    'batch_code' => 'BATCH-101-001',
                    'production_date' => '2026-01-01',
                    'expiry_date' => '2027-01-01',
                    'sncode' => 'SN-101-001',
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 10,
                    'actual_number' => 9,
                    'diff_number' => -1,
                    'price' => 5,
                    'diff_amount' => -5,
                    'remark' => '盘亏1',
                ],
            ],
        ];

        $request = Request::create('/inventory-check/create', 'POST', $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertMatchesRegularExpression('/^IC\d{8}\d{4}$/', $data['data']['key']);
        $this->assertDatabaseHas('inventory_checks', [
            'id' => $data['data']['id'],
            'warehouse_id' => 1,
            'department_id' => 1,
            'user_id' => 11,
            'status' => 1,
            'create_user_id' => $this->authUserId,
        ]);
        $this->assertDatabaseHas('inventory_check_details', [
            'inventory_check_id' => $data['data']['id'],
            'goods_id' => 101,
            'inventory_batchs_id' => 7001,
            'batch_code' => 'BATCH-101-001',
            'production_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'sncode' => 'SN-101-001',
            'actual_number' => 9.0000,
            'diff_number' => -1.0000,
        ]);
    }

    public function test_update_inventory_check_draft_replaces_details(): void
    {
        DB::table('inventory_checks')->insert([
            'id' => 1,
            'key' => 'IC202603220001',
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'department_id' => 1,
            'user_id' => 11,
            'remark' => '原始草稿',
            'status' => 1,
            'check_user' => null,
            'check_time' => null,
            'inventory_loss_id' => null,
            'inventory_overflow_id' => null,
            'create_user_id' => 13,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_check_details')->insert([
            'id' => 1,
            'inventory_check_id' => 1,
            'key' => 'IC202603220001',
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'goods_id' => 101,
            'goods_name' => '测试商品A',
            'specs' => '10片',
            'manufacturer_id' => 301,
            'manufacturer_name' => '厂家A',
            'unit_id' => 201,
            'unit_name' => '盒',
            'book_number' => 10,
            'actual_number' => 9,
            'diff_number' => -1,
            'price' => 5,
            'diff_amount' => -5,
            'remark' => '原明细',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'id' => 1,
            'form' => [
                'date' => '2026-03-23',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 12,
                'remark' => '更新后草稿',
            ],
            'detail' => [
                [
                    'goods_id' => 102,
                    'goods_name' => '测试商品B',
                    'specs' => '20片',
                    'manufacturer_id' => 302,
                    'manufacturer_name' => '厂家B',
                    'inventory_batchs_id' => 7002,
                    'batch_code' => 'BATCH-102-001',
                    'production_date' => '2026-01-02',
                    'expiry_date' => '2027-01-02',
                    'sncode' => 'SN-102-001',
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 20,
                    'actual_number' => 20,
                    'diff_number' => 0,
                    'price' => 6,
                    'diff_amount' => 0,
                    'remark' => '无差异',
                ],
            ],
        ];

        $request = Request::create('/inventory-check/update', 'POST', $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertDatabaseHas('inventory_checks', [
            'id' => 1,
            'date' => '2026-03-23',
            'user_id' => 12,
            'remark' => '更新后草稿',
            'create_user_id' => $this->authUserId,
        ]);
        $this->assertDatabaseMissing('inventory_check_details', [
            'inventory_check_id' => 1,
            'goods_id' => 101,
        ]);
        $this->assertDatabaseHas('inventory_check_details', [
            'inventory_check_id' => 1,
            'goods_id' => 102,
            'inventory_batchs_id' => 7002,
            'batch_code' => 'BATCH-102-001',
            'production_date' => '2026-01-02',
            'expiry_date' => '2027-01-02',
            'sncode' => 'SN-102-001',
        ]);
    }

    public function test_create_rejects_duplicate_inventory_batchs_id_in_same_request(): void
    {
        $payload = [
            'form' => [
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 11,
                'remark' => '重复批次id',
            ],
            'detail' => [
                [
                    'goods_id' => 101,
                    'goods_name' => '测试商品A',
                    'specs' => '10片',
                    'manufacturer_id' => 301,
                    'manufacturer_name' => '厂家A',
                    'inventory_batchs_id' => 7001,
                    'batch_code' => 'BATCH-101-001',
                    'production_date' => '2026-01-01',
                    'expiry_date' => '2027-01-01',
                    'sncode' => 'SN-101-001',
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 10,
                    'actual_number' => 9,
                    'diff_number' => -1,
                    'price' => 5,
                    'diff_amount' => -5,
                ],
                [
                    'goods_id' => 102,
                    'goods_name' => '测试商品B',
                    'specs' => '20片',
                    'manufacturer_id' => 302,
                    'manufacturer_name' => '厂家B',
                    'inventory_batchs_id' => 7001,
                    'batch_code' => 'BATCH-102-001',
                    'production_date' => '2026-01-02',
                    'expiry_date' => '2027-01-02',
                    'sncode' => 'SN-102-001',
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 20,
                    'actual_number' => 18,
                    'diff_number' => -2,
                    'price' => 6,
                    'diff_amount' => -12,
                ],
            ],
        ];

        $request = Request::create('/inventory-check/create', 'POST', $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame(200, $data['code']);
        $this->assertStringContainsString('不能重复', $data['msg']);
    }

    public function test_create_rejects_duplicate_goods_and_batch_code_when_batch_id_is_empty(): void
    {
        $payload = [
            'form' => [
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 11,
                'remark' => '重复商品批号',
            ],
            'detail' => [
                [
                    'goods_id' => 101,
                    'goods_name' => '测试商品A',
                    'specs' => '10片',
                    'manufacturer_id' => 301,
                    'manufacturer_name' => '厂家A',
                    'inventory_batchs_id' => null,
                    'batch_code' => 'NEW-BATCH-101-A',
                    'production_date' => '2026-03-01',
                    'expiry_date' => '2027-03-01',
                    'sncode' => null,
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 0,
                    'actual_number' => 1,
                    'diff_number' => 1,
                    'price' => 5,
                    'diff_amount' => 5,
                ],
                [
                    'goods_id' => 101,
                    'goods_name' => '测试商品A',
                    'specs' => '10片',
                    'manufacturer_id' => 301,
                    'manufacturer_name' => '厂家A',
                    'inventory_batchs_id' => null,
                    'batch_code' => 'NEW-BATCH-101-A',
                    'production_date' => '2026-03-01',
                    'expiry_date' => '2027-03-01',
                    'sncode' => null,
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 0,
                    'actual_number' => 2,
                    'diff_number' => 2,
                    'price' => 5,
                    'diff_amount' => 10,
                ],
            ],
        ];

        $request = Request::create('/inventory-check/create', 'POST', $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame(200, $data['code']);
        $this->assertStringContainsString('不能重复', $data['msg']);
    }

    public function test_create_accepts_new_overflow_batch_and_persists_snapshot_fields(): void
    {
        $payload = [
            'form' => [
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 11,
                'remark' => '新盘盈批次',
            ],
            'detail' => [
                [
                    'goods_id' => 102,
                    'goods_name' => '测试商品B',
                    'specs' => '20片',
                    'manufacturer_id' => 302,
                    'manufacturer_name' => '厂家B',
                    'inventory_batchs_id' => null,
                    'batch_code' => 'NEW-BATCH-102-001',
                    'production_date' => '2026-03-10',
                    'expiry_date' => '2027-03-10',
                    'sncode' => 'SN-NEW-102-001',
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 0,
                    'actual_number' => 3,
                    'diff_number' => 3,
                    'price' => 8.5,
                    'diff_amount' => 25.5,
                    'remark' => '盘盈新批号',
                ],
            ],
        ];

        $request = Request::create('/inventory-check/create', 'POST', $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertDatabaseHas('inventory_check_details', [
            'inventory_check_id' => $data['data']['id'],
            'goods_id' => 102,
            'inventory_batchs_id' => null,
            'batch_code' => 'NEW-BATCH-102-001',
            'production_date' => '2026-03-10',
            'expiry_date' => '2027-03-10',
            'sncode' => 'SN-NEW-102-001',
            'book_number' => 0.0000,
            'actual_number' => 3.0000,
            'diff_number' => 3.0000,
            'price' => 8.5000,
            'diff_amount' => 25.5000,
        ]);
    }

    public function test_create_rejects_existing_batch_when_goods_id_does_not_match(): void
    {
        $payload = [
            'form' => [
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 11,
                'remark' => '批次商品不匹配',
            ],
            'detail' => [
                [
                    'goods_id' => 102,
                    'goods_name' => '测试商品B',
                    'specs' => '20片',
                    'manufacturer_id' => 302,
                    'manufacturer_name' => '厂家B',
                    'inventory_batchs_id' => 7001,
                    'batch_code' => 'BATCH-101-001',
                    'production_date' => '2026-01-01',
                    'expiry_date' => '2027-01-01',
                    'sncode' => 'SN-101-001',
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 10,
                    'actual_number' => 9,
                    'diff_number' => -1,
                    'price' => 5,
                    'diff_amount' => -5,
                ],
            ],
        ];

        $request = Request::create('/inventory-check/create', 'POST', $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame(200, $data['code']);
        $this->assertStringContainsString('商品不匹配', $data['msg']);
    }

    public function test_create_rejects_existing_batch_when_warehouse_does_not_match(): void
    {
        $payload = [
            'form' => [
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'department_id' => 1,
                'user_id' => 11,
                'remark' => '批次仓库不匹配',
            ],
            'detail' => [
                [
                    'goods_id' => 101,
                    'goods_name' => '测试商品A',
                    'specs' => '10片',
                    'manufacturer_id' => 301,
                    'manufacturer_name' => '厂家A',
                    'inventory_batchs_id' => 7003,
                    'batch_code' => 'BATCH-101-003',
                    'production_date' => '2026-01-03',
                    'expiry_date' => '2027-01-03',
                    'sncode' => 'SN-101-003',
                    'unit_id' => 201,
                    'unit_name' => '盒',
                    'book_number' => 10,
                    'actual_number' => 9,
                    'diff_number' => -1,
                    'price' => 5,
                    'diff_amount' => -5,
                ],
            ],
        ];

        $request = Request::create('/inventory-check/create', 'POST', $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame(200, $data['code']);
        $this->assertStringContainsString('仓库不匹配', $data['msg']);
    }

    public function test_remove_inventory_check_draft(): void
    {
        DB::table('inventory_checks')->insert([
            'id' => 1,
            'key' => 'IC202603220001',
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'department_id' => 1,
            'user_id' => 11,
            'remark' => '待删除草稿',
            'status' => 1,
            'check_user' => null,
            'check_time' => null,
            'inventory_loss_id' => null,
            'inventory_overflow_id' => null,
            'create_user_id' => 13,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_check_details')->insert([
            'id' => 1,
            'inventory_check_id' => 1,
            'key' => 'IC202603220001',
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'goods_id' => 101,
            'goods_name' => '测试商品A',
            'specs' => '10片',
            'manufacturer_id' => 301,
            'manufacturer_name' => '厂家A',
            'unit_id' => 201,
            'unit_name' => '盒',
            'book_number' => 10,
            'actual_number' => 9,
            'diff_number' => -1,
            'price' => 5,
            'diff_amount' => -5,
            'remark' => '原明细',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/inventory-check/remove', 'GET', ['id' => 1], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);
        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertDatabaseMissing('inventory_checks', ['id' => 1]);
        $this->assertDatabaseMissing('inventory_check_details', ['inventory_check_id' => 1]);
    }

    private function mockAuthUser(): void
    {
        $user = User::query()->create([
            'name' => '录单人',
            'email' => 'creator@example.com',
            'password' => 'secret',
        ]);
        $this->authUserId = $user->id;
        Auth::shouldReceive('user')->andReturn($user);
    }

    private function createTables(): void
    {
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
            $table->string('keyword')->nullable();
            $table->timestamps();
        });

        Schema::create('goods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
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

        Schema::create('inventory_batchs', function (Blueprint $table) {
            $table->id();
            $table->integer('warehouse_id');
            $table->integer('goods_id');
            $table->string('goods_name')->nullable();
            $table->string('specs')->nullable();
            $table->integer('manufacturer_id')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->integer('unit_id')->nullable();
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
            ['id' => 11, 'name' => '经办人A', 'email' => 'u11@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '经办人B', 'email' => 'u12@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => '录单人', 'email' => 'u13@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('unit')->insert([
            'id' => 201,
            'name' => '盒',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('manufacturer')->insert([
            ['id' => 301, 'name' => '厂家A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 302, 'name' => '厂家B', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('goods')->insert([
            ['id' => 101, 'name' => '测试商品A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'name' => '测试商品B', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('inventory_batchs')->insert([
            [
                'id' => 7001,
                'warehouse_id' => 1,
                'goods_id' => 101,
                'goods_name' => '测试商品A',
                'specs' => '10片',
                'manufacturer_id' => 301,
                'manufacturer_name' => '厂家A',
                'unit_id' => 201,
                'unit_name' => '盒',
                'price' => 5.0000,
                'number' => 10.0000,
                'amount' => 50.0000,
                'batch_code' => 'BATCH-101-001',
                'production_date' => '2026-01-01',
                'expiry_date' => '2027-01-01',
                'sncode' => 'SN-101-001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7002,
                'warehouse_id' => 1,
                'goods_id' => 102,
                'goods_name' => '测试商品B',
                'specs' => '20片',
                'manufacturer_id' => 302,
                'manufacturer_name' => '厂家B',
                'unit_id' => 201,
                'unit_name' => '盒',
                'price' => 6.0000,
                'number' => 20.0000,
                'amount' => 120.0000,
                'batch_code' => 'BATCH-102-001',
                'production_date' => '2026-01-02',
                'expiry_date' => '2027-01-02',
                'sncode' => 'SN-102-001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7003,
                'warehouse_id' => 2,
                'goods_id' => 101,
                'goods_name' => '测试商品A',
                'specs' => '10片',
                'manufacturer_id' => 301,
                'manufacturer_name' => '厂家A',
                'unit_id' => 201,
                'unit_name' => '盒',
                'price' => 5.0000,
                'number' => 10.0000,
                'amount' => 50.0000,
                'batch_code' => 'BATCH-101-003',
                'production_date' => '2026-01-03',
                'expiry_date' => '2027-01-03',
                'sncode' => 'SN-101-003',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
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
