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

class InventoryCheckApprovalTest extends TestCase
{
    private int $authUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('inventory-check/check', 'GET', InventoryCheckController::class.'@check');
        Route::get('/inventory-check/check', [InventoryCheckController::class, 'check']);
        $this->createTables();
        $this->mockAuthUser();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_overflow_details');
        Schema::dropIfExists('inventory_overflows');
        Schema::dropIfExists('inventory_loss_details');
        Schema::dropIfExists('inventory_losses');
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

    public function test_approve_creates_one_draft_loss_when_only_negative_diffs(): void
    {
        $checkId = $this->seedCheckWithDiffs([-2, -1]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertDatabaseCount('inventory_losses', 1);
        $this->assertDatabaseCount('inventory_overflows', 0);
        $this->assertDatabaseHas('inventory_losses', ['status' => 1]);
        $this->assertDatabaseHas('inventory_checks', ['id' => $checkId, 'status' => 2]);
        $this->assertGeneratedLossDetailsUseRealBatches();
    }

    public function test_approve_creates_one_draft_overflow_when_only_positive_diffs(): void
    {
        $checkId = $this->seedCheckWithDiffs([2, 1]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertDatabaseCount('inventory_losses', 0);
        $this->assertDatabaseCount('inventory_overflows', 1);
        $this->assertDatabaseHas('inventory_overflows', ['status' => 1]);
        $this->assertDatabaseHas('inventory_checks', ['id' => $checkId, 'status' => 2]);
        $this->assertGeneratedOverflowDetailsHaveBatchCode();
    }

    public function test_approve_creates_both_draft_loss_and_overflow_when_mixed_diffs(): void
    {
        $checkId = $this->seedCheckWithDiffs([-2, 0, 3]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertDatabaseCount('inventory_losses', 1);
        $this->assertDatabaseCount('inventory_overflows', 1);
        $this->assertDatabaseHas('inventory_losses', ['status' => 1]);
        $this->assertDatabaseHas('inventory_overflows', ['status' => 1]);
        $this->assertDatabaseHas('inventory_checks', ['id' => $checkId, 'status' => 2]);
        $this->assertGeneratedLossDetailsUseRealBatches();
        $this->assertGeneratedOverflowDetailsHaveBatchCode();
    }

    public function test_approve_creates_none_when_all_zero_diffs(): void
    {
        $checkId = $this->seedCheckWithDiffs([0, 0]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertDatabaseCount('inventory_losses', 0);
        $this->assertDatabaseCount('inventory_overflows', 0);
        $this->assertDatabaseHas('inventory_checks', ['id' => $checkId, 'status' => 2]);
    }

    public function test_repeated_approval_is_rejected(): void
    {
        $checkId = $this->seedCheckWithDiffs([-2, 3]);

        $first = $this->dispatchCheck($checkId);
        $firstPayload = json_decode($first->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(200, $firstPayload['code']);

        $second = $this->dispatchCheck($checkId);
        $secondPayload = json_decode($second->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $second->getStatusCode());
        $this->assertNotSame(200, $secondPayload['code']);
    }

    public function test_generated_loss_and_overflow_rows_remain_draft_status(): void
    {
        $checkId = $this->seedCheckWithDiffs([-1, 2]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $payload['code']);
        $this->assertDatabaseHas('inventory_losses', ['status' => 1]);
        $this->assertDatabaseHas('inventory_overflows', ['status' => 1]);
        $this->assertGeneratedLossDetailsUseRealBatches();
        $this->assertGeneratedOverflowDetailsHaveBatchCode();
    }

    public function test_approve_uses_referenced_existing_batch_for_negative_diff(): void
    {
        $checkId = $this->seedCheckWithDiffs([-1]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);

        $lossId = (int) DB::table('inventory_losses')->value('id');
        $lossDetails = DB::table('inventory_loss_details')
            ->where('inventory_loss_id', $lossId)
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $lossDetails);
        $this->assertSame(1, (int) $lossDetails[0]->inventory_batchs_id);
        $this->assertSame('BATCH-101-A', $lossDetails[0]->batch_code);
        $this->assertEqualsWithDelta(1, (float) $lossDetails[0]->number, 0.0001);
    }

    public function test_approve_preserves_existing_batch_snapshot_on_positive_diff(): void
    {
        $checkId = $this->seedCheckWithDiffs([2]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);

        $overflowId = (int) DB::table('inventory_overflows')->value('id');
        $this->assertDatabaseHas('inventory_overflow_details', [
            'inventory_overflow_id' => $overflowId,
            'goods_id' => 101,
            'batch_code' => 'BATCH-101-A',
            'production_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'sncode' => 'SN-101-A',
        ]);
    }

    public function test_approve_overflow_fills_usable_unit_when_check_detail_unit_missing(): void
    {
        $checkId = $this->seedCheckWithDiffs([2], [
            ['unit_id' => null, 'unit_name' => null],
        ]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertGeneratedOverflowDetailsHaveUsableUnit();
    }

    public function test_approve_rejects_new_batch_detail_when_diff_is_negative(): void
    {
        $checkId = $this->seedCheckWithDiffs([-1], [[
            'goods_id' => 103,
            'goods_name' => '测试商品C',
            'inventory_batchs_id' => null,
            'batch_code' => 'NEW-BATCH-103-N',
            'production_date' => '2026-03-01',
            'expiry_date' => '2027-03-01',
            'sncode' => 'SN-103-N',
            'unit_id' => 201,
            'unit_name' => '盒',
        ]]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame(200, $payload['code']);
        $this->assertDatabaseCount('inventory_losses', 0);
        $this->assertDatabaseCount('inventory_overflows', 0);
    }

    public function test_approve_accepts_new_batch_detail_when_diff_is_positive_and_creates_overflow_detail(): void
    {
        $checkId = $this->seedCheckWithDiffs([2], [[
            'goods_id' => 103,
            'goods_name' => '测试商品C',
            'manufacturer_id' => 301,
            'manufacturer_name' => '厂家A',
            'inventory_batchs_id' => null,
            'batch_code' => 'NEW-BATCH-103-P',
            'production_date' => '2026-03-02',
            'expiry_date' => '2027-03-02',
            'sncode' => 'SN-103-P',
            'unit_id' => 201,
            'unit_name' => '盒',
        ]]);
        $response = $this->dispatchCheck($checkId);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);

        $overflowId = (int) DB::table('inventory_overflows')->value('id');
        $this->assertDatabaseHas('inventory_overflow_details', [
            'inventory_overflow_id' => $overflowId,
            'goods_id' => 103,
            'number' => 2.0000,
            'unit_id' => 201,
            'unit_name' => '盒',
            'manufacturer_id' => 301,
            'manufacturer_name' => '厂家A',
            'batch_code' => 'NEW-BATCH-103-P',
            'production_date' => '2026-03-02',
            'expiry_date' => '2027-03-02',
            'sncode' => 'SN-103-P',
        ]);
    }

    private function dispatchCheck(int $checkId)
    {
        $request = Request::create('/inventory-check/check', 'GET', ['id' => $checkId], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        app()->instance('request', $request);

        return app('router')->dispatch($request);
    }

    /**
     * @param  array<int, float|int>  $diffs
     * @param  array<int, array<string, mixed>>  $detailOverrides
     */
    private function seedCheckWithDiffs(array $diffs, array $detailOverrides = []): int
    {
        $checkId = (int) (DB::table('inventory_checks')->max('id') ?? 0) + 1;
        $key = sprintf('IC20260322%04d', $checkId);

        DB::table('inventory_checks')->insert([
            'id' => $checkId,
            'key' => $key,
            'date' => '2026-03-22',
            'warehouse_id' => 1,
            'department_id' => 1,
            'user_id' => 11,
            'remark' => '审批测试',
            'status' => 1,
            'check_user' => null,
            'check_time' => null,
            'inventory_loss_id' => null,
            'inventory_overflow_id' => null,
            'create_user_id' => 13,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($diffs as $idx => $diff) {
            $overrides = $detailOverrides[$idx] ?? [];
            $goodsId = $overrides['goods_id'] ?? (101 + $idx);
            $batch = DB::table('inventory_batchs')
                ->where('warehouse_id', 1)
                ->where('goods_id', $goodsId)
                ->orderBy('id')
                ->first();
            DB::table('inventory_check_details')->insert([
                'id' => ($checkId * 10) + $idx + 1,
                'inventory_check_id' => $checkId,
                'key' => $key,
                'date' => '2026-03-22',
                'warehouse_id' => 1,
                'goods_id' => $goodsId,
                'goods_name' => $overrides['goods_name'] ?? ('测试商品'.($idx + 1)),
                'specs' => $overrides['specs'] ?? '10片',
                'manufacturer_id' => $overrides['manufacturer_id'] ?? $batch?->manufacturer_id ?? 301,
                'manufacturer_name' => $overrides['manufacturer_name'] ?? $batch?->manufacturer_name ?? '厂家A',
                'inventory_batchs_id' => $overrides['inventory_batchs_id'] ?? $batch?->id ?? null,
                'batch_code' => $overrides['batch_code'] ?? $batch?->batch_code ?? ('CHECK-BATCH-'.($idx + 1)),
                'production_date' => $overrides['production_date'] ?? $batch?->production_date ?? null,
                'expiry_date' => $overrides['expiry_date'] ?? $batch?->expiry_date ?? null,
                'sncode' => $overrides['sncode'] ?? $batch?->sncode ?? null,
                'unit_id' => $overrides['unit_id'] ?? 201,
                'unit_name' => $overrides['unit_name'] ?? '盒',
                'book_number' => 10,
                'actual_number' => 10 + $diff,
                'diff_number' => $diff,
                'price' => 5,
                'diff_amount' => $diff * 5,
                'remark' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $checkId;
    }

    private function mockAuthUser(): void
    {
        $user = User::query()->create([
            'name' => '审批人',
            'email' => 'checker@example.com',
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

        Schema::create('inventory_batchs', function (Blueprint $table) {
            $table->id();
            $table->integer('goods_id');
            $table->string('goods_name');
            $table->string('specs')->nullable();
            $table->integer('warehouse_id');
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('number', 14, 4)->default(0);
            $table->integer('unit_id');
            $table->string('unit_name');
            $table->decimal('amount', 14, 4)->default(0);
            $table->integer('manufacturer_id')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_code')->nullable();
            $table->string('sncode')->nullable();
            $table->text('remark')->nullable();
            $table->string('batchable_type')->nullable();
            $table->integer('batchable_id')->nullable();
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

        Schema::create('inventory_losses', function (Blueprint $table) {
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
            $table->decimal('amount', 14, 4)->default(0);
            $table->integer('create_user_id');
            $table->timestamps();
        });

        Schema::create('inventory_loss_details', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->date('date');
            $table->integer('inventory_loss_id');
            $table->integer('warehouse_id');
            $table->integer('department_id');
            $table->integer('goods_id');
            $table->string('goods_name');
            $table->string('specs')->nullable();
            $table->integer('manufacturer_id')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->integer('inventory_batchs_id')->default(0);
            $table->string('batch_code')->default('');
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('unit_id')->default(0);
            $table->string('unit_name')->default('');
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('number', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->string('sncode')->nullable();
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('inventory_overflows', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->date('date');
            $table->integer('warehouse_id');
            $table->integer('department_id');
            $table->integer('user_id');
            $table->text('remark')->nullable();
            $table->decimal('amount', 14, 4)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->integer('check_user')->nullable();
            $table->dateTime('check_time')->nullable();
            $table->integer('create_user_id');
            $table->timestamps();
        });

        Schema::create('inventory_overflow_details', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->date('date');
            $table->tinyInteger('status')->default(1);
            $table->integer('inventory_overflow_id');
            $table->integer('warehouse_id');
            $table->integer('goods_id');
            $table->string('goods_name');
            $table->string('specs')->nullable();
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('number', 14, 4)->default(0);
            $table->integer('unit_id')->default(0);
            $table->string('unit_name', 10)->default('');
            $table->decimal('amount', 14, 4)->default(0);
            $table->integer('manufacturer_id')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_code')->nullable();
            $table->string('sncode')->nullable();
            $table->text('remark')->nullable();
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
            ['id' => 11, 'name' => '经办人A', 'email' => 'u11@example.com', 'password' => 'secret', 'keyword' => 'a', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => '录单人', 'email' => 'u13@example.com', 'password' => 'secret', 'keyword' => 'b', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('unit')->insert([
            'id' => 201,
            'name' => '盒',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('manufacturer')->insert([
            'id' => 301,
            'name' => '厂家A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('goods')->insert([
            ['id' => 101, 'name' => '测试商品A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'name' => '测试商品B', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'name' => '测试商品C', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('inventory_batchs')->insert([
            [
                'id' => 1,
                'goods_id' => 101,
                'goods_name' => '测试商品A',
                'specs' => '10片',
                'warehouse_id' => 1,
                'price' => 5.0000,
                'number' => 4.0000,
                'unit_id' => 201,
                'unit_name' => '盒',
                'amount' => 20.0000,
                'manufacturer_id' => 301,
                'manufacturer_name' => '厂家A',
                'production_date' => '2026-01-01',
                'expiry_date' => '2027-01-01',
                'batch_code' => 'BATCH-101-A',
                'sncode' => 'SN-101-A',
                'remark' => null,
                'batchable_type' => 'seed',
                'batchable_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'goods_id' => 101,
                'goods_name' => '测试商品A',
                'specs' => '10片',
                'warehouse_id' => 1,
                'price' => 5.0000,
                'number' => 3.0000,
                'unit_id' => 201,
                'unit_name' => '盒',
                'amount' => 15.0000,
                'manufacturer_id' => 301,
                'manufacturer_name' => '厂家A',
                'production_date' => '2026-01-02',
                'expiry_date' => '2027-01-02',
                'batch_code' => 'BATCH-101-B',
                'sncode' => 'SN-101-B',
                'remark' => null,
                'batchable_type' => 'seed',
                'batchable_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'goods_id' => 102,
                'goods_name' => '测试商品B',
                'specs' => '10片',
                'warehouse_id' => 1,
                'price' => 5.0000,
                'number' => 10.0000,
                'unit_id' => 201,
                'unit_name' => '盒',
                'amount' => 50.0000,
                'manufacturer_id' => 301,
                'manufacturer_name' => '厂家A',
                'production_date' => '2026-01-03',
                'expiry_date' => '2027-01-03',
                'batch_code' => 'BATCH-102-A',
                'sncode' => 'SN-102-A',
                'remark' => null,
                'batchable_type' => 'seed',
                'batchable_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function assertGeneratedLossDetailsUseRealBatches(): void
    {
        $rows = DB::table('inventory_loss_details')->get();
        $this->assertTrue($rows->isNotEmpty(), 'Expected generated inventory_loss_details rows.');

        foreach ($rows as $row) {
            $this->assertTrue((int) $row->inventory_batchs_id > 0);
            $this->assertNotSame('', trim((string) $row->batch_code));
            $this->assertDatabaseHas('inventory_batchs', [
                'id' => (int) $row->inventory_batchs_id,
                'batch_code' => $row->batch_code,
            ]);
        }
    }

    private function assertGeneratedOverflowDetailsHaveBatchCode(): void
    {
        $rows = DB::table('inventory_overflow_details')->get();
        $this->assertTrue($rows->isNotEmpty(), 'Expected generated inventory_overflow_details rows.');

        foreach ($rows as $row) {
            $this->assertNotSame('', trim((string) $row->batch_code));
        }
    }

    private function assertGeneratedOverflowDetailsHaveUsableUnit(): void
    {
        $rows = DB::table('inventory_overflow_details')->get();
        $this->assertTrue($rows->isNotEmpty(), 'Expected generated inventory_overflow_details rows.');

        foreach ($rows as $row) {
            $this->assertTrue((int) $row->unit_id > 0);
            $this->assertNotSame('', trim((string) $row->unit_name));
        }
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
