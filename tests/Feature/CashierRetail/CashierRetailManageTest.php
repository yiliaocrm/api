<?php

namespace Tests\Feature\CashierRetail;

use App\Enums\CashierRetailStatus;
use App\Http\Controllers\Web\CashierRetailController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierRetailManageTest extends TestCase
{
    private string $originalTablePrefix = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('cashier-retail/manage', 'POST', CashierRetailController::class.'@manage');
        Route::post('/cashier-retail/manage', [CashierRetailController::class, 'manage']);
        $this->originalTablePrefix = DB::connection()->getTablePrefix();
        DB::connection()->setTablePrefix('cy_');
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('cashier_pay');
        Schema::dropIfExists('cashier_retail');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('users');
        DB::connection()->setTablePrefix($this->originalTablePrefix);

        parent::tearDown();
    }

    public function test_manage_returns_only_rows_in_date_range(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['total']);
        $this->assertCount(2, $payload['data']['rows']);
        $this->assertSame('retail-1', $payload['data']['rows'][0]['id']);
        $this->assertSame('retail-2', $payload['data']['rows'][1]['id']);
    }

    public function test_manage_returns_footer_with_page_subtotal_and_total_summary(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 1,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertCount(2, $payload['data']['footer']);
        $this->assertSame('页小计:', $payload['data']['footer'][0]['status']);
        $this->assertSame('总合计:', $payload['data']['footer'][1]['status']);

        $this->assertSame(100.0, (float) $payload['data']['footer'][0]['payable']);
        $this->assertSame(70.0, (float) $payload['data']['footer'][0]['income']);
        $this->assertSame(10.0, (float) $payload['data']['footer'][0]['deposit']);
        $this->assertSame(20.0, (float) $payload['data']['footer'][0]['arrearage']);

        $this->assertSame(300.0, (float) $payload['data']['footer'][1]['payable']);
        $this->assertSame(220.0, (float) $payload['data']['footer'][1]['income']);
        $this->assertSame(30.0, (float) $payload['data']['footer'][1]['deposit']);
        $this->assertSame(50.0, (float) $payload['data']['footer'][1]['arrearage']);
    }

    public function test_manage_keyword_hits_customer_keyword(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'keyword' => '目标',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('retail-1', $payload['data']['rows'][0]['id']);
        $this->assertSame('客户A', $payload['data']['rows'][0]['customer']['name']);
    }

    public function test_manage_filters_with_status_one_return_pending_rows_only(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertCount(1, $payload['data']['rows']);
        $this->assertSame('retail-1', $payload['data']['rows'][0]['id']);
        $this->assertSame(CashierRetailStatus::PENDING->value, $payload['data']['rows'][0]['status']);
        $this->assertSame('挂单', $payload['data']['rows'][0]['status_text']);
    }

    public function test_manage_sorts_on_cashier_retail_created_at_correctly(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'sort' => 'created_at',
            'order' => 'desc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['total']);
        $this->assertSame('retail-2', $payload['data']['rows'][0]['id']);
        $this->assertSame('retail-1', $payload['data']['rows'][1]['id']);
    }

    public function test_manage_rejects_invalid_sort_cleanly(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'sort' => 'invalid_column',
            'order' => 'asc',
        ]);

        $this->assertNotSame(500, $payload['code'] ?? 500, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function test_manage_rejects_malformed_between_filter_value_cleanly(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'created_at',
                    'operator' => 'between',
                    'value' => 'oops',
                ],
            ],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertNotSame(500, $payload['code'] ?? 500, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function test_manage_rejects_non_array_filter_element_cleanly(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => ['oops'],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertNotSame(500, $payload['code'] ?? 500, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function test_manage_accepts_not_equal_angle_operator_when_configured(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '<>',
                    'value' => 2,
                ],
            ],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('retail-1', $payload['data']['rows'][0]['id']);
    }

    public function test_manage_projects_user_relation_to_id_and_name_only(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertArrayHasKey('user', $payload['data']['rows'][0]);
        $this->assertSame(['id', 'name'], array_keys($payload['data']['rows'][0]['user']));
    }

    private function dispatchManage(array $data): array
    {
        $request = Request::create(
            '/cashier-retail/manage',
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
        DB::table('scene_fields')->insert([
            [
                'page' => 'CashierRetailIndex',
                'name' => '状态',
                'table' => 'cashier_retail',
                'field' => 'status',
                'field_alias' => 'status',
                'field_type' => 'integer',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '不等于兼容', 'value' => '!='],
                ], JSON_THROW_ON_ERROR),
                'query_config' => null,
                'keyword' => '状态',
            ],
            [
                'page' => 'CashierRetailIndex',
                'name' => '创建时间',
                'table' => 'cashier_retail',
                'field' => 'created_at',
                'field_alias' => 'created_at',
                'field_type' => 'datetime',
                'operators' => json_encode([
                    ['text' => '在区间', 'value' => 'between'],
                ], JSON_THROW_ON_ERROR),
                'query_config' => null,
                'keyword' => '创建时间',
            ],
        ]);
    }

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => '收银员甲', 'email' => 'a@example.com', 'password' => 'secret'],
            ['id' => 2, 'name' => '收银员乙', 'email' => 'b@example.com', 'password' => 'secret'],
        ]);
    }

    private function seedCustomers(): void
    {
        DB::table('customer')->insert([
            [
                'id' => 'customer-1',
                'name' => '客户A',
                'idcard' => 'ID-A',
                'balance' => 0,
                'keyword' => '客户A,目标,匹配',
                'created_at' => '2026-03-01 09:00:00',
                'updated_at' => '2026-03-01 09:00:00',
            ],
            [
                'id' => 'customer-2',
                'name' => '客户B',
                'idcard' => 'ID-B',
                'balance' => 0,
                'keyword' => '客户B,其他',
                'created_at' => '2026-03-01 09:00:00',
                'updated_at' => '2026-03-01 09:00:00',
            ],
        ]);
    }

    private function seedRetails(): void
    {
        DB::table('cashier_retail')->insert([
            [
                'id' => 'retail-1',
                'customer_id' => 'customer-1',
                'cashier_id' => null,
                'amount' => 100.0000,
                'payable' => 100.0,
                'income' => 70.0,
                'deposit' => 10.0,
                'arrearage' => 20.0,
                'remark' => '零售单1',
                'detail' => json_encode([], JSON_THROW_ON_ERROR),
                'status' => 1,
                'type' => 1,
                'user_id' => 1,
                'created_at' => '2026-03-10 10:00:00',
                'updated_at' => '2026-03-10 10:00:00',
            ],
            [
                'id' => 'retail-2',
                'customer_id' => 'customer-2',
                'cashier_id' => null,
                'amount' => 200.0000,
                'payable' => 200.0,
                'income' => 150.0,
                'deposit' => 20.0,
                'arrearage' => 30.0,
                'remark' => '零售单2',
                'detail' => json_encode([], JSON_THROW_ON_ERROR),
                'status' => 2,
                'type' => 2,
                'user_id' => 2,
                'created_at' => '2026-03-20 12:00:00',
                'updated_at' => '2026-03-20 12:00:00',
            ],
            [
                'id' => 'retail-out',
                'customer_id' => 'customer-1',
                'cashier_id' => null,
                'amount' => 300.0000,
                'payable' => 300.0,
                'income' => 280.0,
                'deposit' => 0.0,
                'arrearage' => 20.0,
                'remark' => '区间外数据',
                'detail' => json_encode([], JSON_THROW_ON_ERROR),
                'status' => 1,
                'type' => 1,
                'user_id' => 1,
                'created_at' => '2026-02-20 12:00:00',
                'updated_at' => '2026-02-20 12:00:00',
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

        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->decimal('balance', 14, 4)->default(0);
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('cashier_retail', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->uuid('cashier_id')->nullable();
            $table->decimal('amount', 14, 4);
            $table->decimal('payable', 14, 4)->default(0);
            $table->decimal('income', 14, 4)->default(0);
            $table->decimal('deposit', 14, 4)->default(0);
            $table->decimal('arrearage', 14, 4)->default(0);
            $table->text('remark')->nullable();
            $table->text('detail')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('type')->default(1);
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('cashier_pay', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('cashier_id')->nullable()->index();
            $table->uuid('customer_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->decimal('income', 14, 4)->default(0);
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
