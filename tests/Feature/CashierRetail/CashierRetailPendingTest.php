<?php

namespace Tests\Feature\CashierRetail;

use App\Enums\CashierRetailStatus;
use App\Http\Controllers\Web\CashierRetailController;
use App\Models\User;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierRetailPendingTest extends TestCase
{
    private ?AuthenticatableContract $loginUser = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists(
            'cashier-retail/pending',
            'POST',
            CashierRetailController::class.'@pending'
        );

        $this->dropFixtureTables();
        $this->createTables();
        $this->seedBaseData();
    }

    protected function tearDown(): void
    {
        auth()->logout();
        $this->dropFixtureTables();

        parent::tearDown();
    }

    public function test_pending_creates_retail_and_details(): void
    {
        $this->actingAsCashierUser(101);

        $payload = $this->dispatchPending([
            'customer_id' => 'customer-1',
            'medium_id' => 1,
            'type' => 1,
            'remark' => '首次挂单',
            'detail' => [
                [
                    'type' => 'product',
                    'product_id' => 11,
                    'product_name' => '皮肤管理',
                    'times' => 1,
                    'price' => 80,
                    'sales_price' => 80,
                    'payable' => 80,
                    'department_id' => 9,
                    'salesman' => [['user_id' => 7, 'ratio' => 100]],
                    'remark' => '主项目',
                ],
                [
                    'type' => 'goods',
                    'goods_id' => 22,
                    'goods_name' => '修复面膜',
                    'times' => 2,
                    'price' => 35,
                    'sales_price' => 35,
                    'payable' => 70,
                    'department_id' => 9,
                    'salesman' => [['user_id' => 8, 'ratio' => 100]],
                    'remark' => '赠品改收费',
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $retailId = $payload['data']['id'] ?? null;
        $this->assertNotNull($retailId, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $retail = DB::table('cashier_retail')->where('id', $retailId)->first();
        $this->assertNotNull($retail);
        $this->assertSame('customer-1', $retail->customer_id);
        $this->assertSame(1, (int) $retail->medium_id);
        $this->assertSame(1, (int) $retail->type);
        $this->assertSame(CashierRetailStatus::PENDING->value, (int) $retail->status);
        $this->assertSame(150.0, (float) $retail->payable);
        $this->assertSame(101, (int) $retail->user_id);

        $details = DB::table('cashier_retail_detail')
            ->where('cashier_retail_id', $retailId)
            ->orderBy('payable')
            ->get();

        $this->assertCount(2, $details);
        $this->assertSame(70.0, (float) $details[0]->payable);
        $this->assertSame(80.0, (float) $details[1]->payable);
        $this->assertSame(101, (int) $details[0]->user_id);
        $this->assertSame(101, (int) $details[1]->user_id);
    }

    public function test_pending_updates_existing_pending_retail_and_replaces_old_details(): void
    {
        $this->actingAsCashierUser(202);

        DB::table('cashier_retail')->insert([
            'id' => 'retail-1',
            'customer_id' => 'customer-1',
            'medium_id' => 1,
            'type' => 1,
            'status' => CashierRetailStatus::PENDING->value,
            'payable' => 999,
            'remark' => '旧挂单',
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cashier_retail_detail')->insert([
            [
                'id' => 'old-detail-1',
                'cashier_retail_id' => 'retail-1',
                'customer_id' => 'customer-1',
                'type' => 'product',
                'product_id' => 10,
                'product_name' => '旧明细A',
                'times' => 1,
                'price' => 10,
                'sales_price' => 10,
                'payable' => 10,
                'amount' => 0,
                'department_id' => 9,
                'salesman' => json_encode([['user_id' => 1]], JSON_THROW_ON_ERROR),
                'remark' => '旧数据',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'old-detail-2',
                'cashier_retail_id' => 'retail-1',
                'customer_id' => 'customer-1',
                'type' => 'goods',
                'goods_id' => 20,
                'goods_name' => '旧明细B',
                'times' => 1,
                'price' => 20,
                'sales_price' => 20,
                'payable' => 20,
                'amount' => 0,
                'department_id' => 9,
                'salesman' => json_encode([['user_id' => 1]], JSON_THROW_ON_ERROR),
                'remark' => '旧数据',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = $this->dispatchPending([
            'id' => 'retail-1',
            'customer_id' => 'customer-2',
            'medium_id' => 2,
            'type' => 2,
            'remark' => '更新挂单',
            'detail' => [
                [
                    'type' => 'product',
                    'product_id' => 31,
                    'product_name' => '新明细A',
                    'times' => 1,
                    'price' => 100,
                    'sales_price' => 100,
                    'payable' => 100,
                    'department_id' => 9,
                    'salesman' => [['user_id' => 2, 'ratio' => 100]],
                    'remark' => '新项目',
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame('retail-1', $payload['data']['id'] ?? null);

        $retail = DB::table('cashier_retail')->where('id', 'retail-1')->first();
        $this->assertNotNull($retail);
        $this->assertSame(CashierRetailStatus::PENDING->value, (int) $retail->status);
        $this->assertSame('customer-2', $retail->customer_id);
        $this->assertSame(2, (int) $retail->medium_id);
        $this->assertSame(2, (int) $retail->type);
        $this->assertSame(100.0, (float) $retail->payable);
        $this->assertSame('更新挂单', $retail->remark);
        $this->assertSame(202, (int) $retail->user_id);

        $details = DB::table('cashier_retail_detail')
            ->where('cashier_retail_id', 'retail-1')
            ->orderBy('created_at')
            ->get();

        $this->assertCount(1, $details);
        $this->assertSame('customer-2', $details[0]->customer_id);
        $this->assertSame('product', $details[0]->type);
        $this->assertSame('新明细A', $details[0]->product_name);
        $this->assertSame(202, (int) $details[0]->user_id);

        $oldCount = DB::table('cashier_retail_detail')
            ->whereIn('id', ['old-detail-1', 'old-detail-2'])
            ->count();
        $this->assertSame(0, $oldCount);
    }

    private function dispatchPending(array $data): array
    {
        $request = Request::create(
            '/cashier-retail/pending',
            'POST',
            $data,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );

        app()->instance('request', $request);
        if ($this->loginUser) {
            auth()->shouldUse('web');
            auth('web')->setUser($this->loginUser);
        }

        $response = app('router')->dispatch($request);
        $decoded = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function actingAsCashierUser(int $id): void
    {
        $user = new class($id) extends User implements AuthenticatableContract
        {
            use Authenticatable;

            public function __construct(int $id)
            {
                $this->id = $id;
                $this->name = 'U'.$id;
            }
        };

        $this->loginUser = $user;
        auth()->shouldUse('web');
        auth('web')->setUser($user);
    }

    private function createTables(): void
    {
        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('medium', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('cashier_retail', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->unsignedInteger('medium_id');
            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('status')->default(CashierRetailStatus::PENDING->value);
            $table->decimal('payable', 14, 4)->default(0);
            $table->text('remark')->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });

        Schema::create('cashier_retail_detail', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('cashier_retail_id')->index();
            $table->uuid('customer_id')->index();
            $table->string('type');
            $table->unsignedInteger('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->unsignedTinyInteger('splitable')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('goods_id')->nullable();
            $table->string('goods_name')->nullable();
            $table->unsignedInteger('times');
            $table->unsignedInteger('unit_id')->nullable();
            $table->string('specs')->nullable();
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('sales_price', 14, 4)->default(0);
            $table->decimal('payable', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->unsignedInteger('department_id');
            $table->text('salesman')->nullable();
            $table->text('remark')->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('customer')->insert([
            [
                'id' => 'customer-1',
                'name' => '测试顾客A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'customer-2',
                'name' => '测试顾客B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('medium')->insert([
            [
                'id' => 1,
                'name' => '线上来源',
            ],
            [
                'id' => 2,
                'name' => '线下来源',
            ],
        ]);
    }

    private function dropFixtureTables(): void
    {
        Schema::dropIfExists('cashier_retail_detail');
        Schema::dropIfExists('cashier_retail');
        Schema::dropIfExists('medium');
        Schema::dropIfExists('customer');
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
