<?php

namespace Tests\Feature\Erkai;

use App\Http\Controllers\Web\ErkaiController;
use App\Http\Requests\Erkai\ErkaiRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ErkaiControllerTest extends TestCase
{
    private string $originalTablePrefix = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('erkai/manage', 'POST', ErkaiController::class.'@manage');
        $this->assertApplicationRouteExists('erkai/info', 'GET', ErkaiController::class.'@info');
        Route::post('/erkai/manage', [ErkaiController::class, 'manage']);
        Route::get('/erkai/info', [ErkaiController::class, 'info']);

        $this->originalTablePrefix = DB::connection()->getTablePrefix();
        DB::connection()->setTablePrefix('cy_');
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('goods_unit');
        Schema::dropIfExists('erkai_detail');
        Schema::dropIfExists('erkai');
        Schema::dropIfExists('department');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('users');
        DB::connection()->setTablePrefix($this->originalTablePrefix);

        parent::tearDown();
    }

    public function test_controller_actions_use_unified_erkai_request(): void
    {
        foreach (['manage', 'info', 'create', 'update'] as $methodName) {
            $method = new \ReflectionMethod(ErkaiController::class, $methodName);
            $parameter = $method->getParameters()[0] ?? null;

            $this->assertNotNull($parameter);
            $this->assertSame(ErkaiRequest::class, $parameter->getType()?->getName());
        }
    }

    public function test_manage_supports_keyword_date_and_scene_filters(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedDepartments();
        $this->seedErkaiRows();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'keyword' => '目标',
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
        $this->assertSame('erkai-1', $payload['data']['rows'][0]['id']);
        $this->assertSame('待收费', $payload['data']['rows'][0]['status_text']);
        $this->assertSame('客户A', $payload['data']['rows'][0]['customer']['name']);
        $this->assertSame('市场部', $payload['data']['rows'][0]['department']['name']);
        $this->assertSame('录单员甲', $payload['data']['rows'][0]['user']['name']);
    }

    public function test_manage_returns_page_and_total_footer_summary(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedDepartments();
        $this->seedErkaiRows();

        $payload = $this->dispatchManage([
            'rows' => 1,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['total']);
        $this->assertSame([
            [
                'customer.name' => '页小计:',
                'payable' => 100,
                'income' => 0,
                'deposit' => 0,
                'coupon' => 0,
                'arrearage' => 100,
            ],
            [
                'customer.name' => '总合计:',
                'payable' => 300,
                'income' => 100,
                'deposit' => 50,
                'coupon' => 0,
                'arrearage' => 150,
            ],
        ], $payload['data']['footer']);
    }

    public function test_manage_supports_array_filters_for_equals_and_not_equals_selects(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedDepartments();
        $this->seedErkaiRows();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => [2, 1],
                ],
                [
                    'field' => 'department_id',
                    'operator' => '<>',
                    'value' => [20],
                ],
            ],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertCount(1, $payload['data']['rows']);
        $this->assertSame('erkai-1', $payload['data']['rows'][0]['id']);
    }

    public function test_info_returns_customer_and_details_units(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedDepartments();
        $this->seedErkaiRows();
        $this->seedErkaiDetailsAndUnits();

        $payload = $this->dispatchInfo(['id' => 'erkai-1']);

        $this->assertSame(200, $payload['code']);
        $this->assertSame('erkai-1', $payload['data']['id']);
        $this->assertSame('customer-1', $payload['data']['customer']['id']);
        $this->assertSame('客户A', $payload['data']['customer']['name']);
        $this->assertNotEmpty($payload['data']['details']);
        $this->assertSame('detail-1', $payload['data']['details'][0]['id']);
        $this->assertIsArray($payload['data']['details'][0]['units']);
        $this->assertSame(11, $payload['data']['details'][0]['units'][0]['unit_id']);
    }

    private function dispatchManage(array $data): array
    {
        $request = Request::create(
            '/erkai/manage',
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

    private function dispatchInfo(array $data): array
    {
        $request = Request::create(
            '/erkai/info',
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
        DB::table('scene_fields')->insert([
            [
                'page' => 'ErkaiIndex',
                'name' => '成交状态',
                'table' => 'erkai',
                'field' => 'status',
                'field_alias' => 'status',
                'field_type' => 'integer',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ], JSON_THROW_ON_ERROR),
                'query_config' => null,
                'keyword' => '状态',
            ],
            [
                'page' => 'ErkaiIndex',
                'name' => '二开科室',
                'table' => 'erkai',
                'field' => 'department_id',
                'field_alias' => 'department_id',
                'field_type' => 'integer',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ], JSON_THROW_ON_ERROR),
                'query_config' => null,
                'keyword' => '科室',
            ],
        ]);
    }

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => '录单员甲', 'email' => 'a@example.com', 'password' => 'secret'],
            ['id' => 2, 'name' => '录单员乙', 'email' => 'b@example.com', 'password' => 'secret'],
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

    private function seedDepartments(): void
    {
        DB::table('department')->insert([
            ['id' => 10, 'name' => '市场部'],
            ['id' => 20, 'name' => '咨询部'],
        ]);
    }

    private function seedErkaiRows(): void
    {
        DB::table('erkai')->insert([
            [
                'id' => 'erkai-1',
                'customer_id' => 'customer-1',
                'department_id' => 10,
                'type' => 1,
                'status' => 1,
                'medium_id' => 1,
                'payable' => 100,
                'income' => 0,
                'deposit' => 0,
                'coupon' => 0,
                'arrearage' => 100,
                'remark' => '二开单1',
                'user_id' => 1,
                'created_at' => '2026-03-10 10:00:00',
                'updated_at' => '2026-03-10 10:00:00',
            ],
            [
                'id' => 'erkai-2',
                'customer_id' => 'customer-2',
                'department_id' => 20,
                'type' => 1,
                'status' => 2,
                'medium_id' => 1,
                'payable' => 200,
                'income' => 100,
                'deposit' => 50,
                'coupon' => 0,
                'arrearage' => 50,
                'remark' => '二开单2',
                'user_id' => 2,
                'created_at' => '2026-03-15 10:00:00',
                'updated_at' => '2026-03-15 10:00:00',
            ],
            [
                'id' => 'erkai-out',
                'customer_id' => 'customer-1',
                'department_id' => 10,
                'type' => 1,
                'status' => 1,
                'medium_id' => 1,
                'payable' => 300,
                'income' => 0,
                'deposit' => 0,
                'coupon' => 0,
                'arrearage' => 300,
                'remark' => '区间外数据',
                'user_id' => 1,
                'created_at' => '2026-02-01 10:00:00',
                'updated_at' => '2026-02-01 10:00:00',
            ],
        ]);
    }

    private function seedErkaiDetailsAndUnits(): void
    {
        DB::table('erkai_detail')->insert([
            [
                'id' => 'detail-1',
                'erkai_id' => 'erkai-1',
                'customer_id' => 'customer-1',
                'status' => 2,
                'type' => 'goods',
                'package_id' => null,
                'package_name' => null,
                'splitable' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => 100,
                'goods_name' => '测试商品',
                'times' => 1,
                'unit_id' => 11,
                'unit_name' => '盒',
                'specs' => '规格A',
                'price' => 120,
                'sales_price' => 100,
                'payable' => 100,
                'amount' => 0,
                'coupon' => 0,
                'department_id' => 10,
                'salesman' => json_encode([1], JSON_THROW_ON_ERROR),
                'remark' => null,
                'user_id' => 1,
                'created_at' => '2026-03-10 10:00:00',
                'updated_at' => '2026-03-10 10:00:00',
            ],
        ]);

        DB::table('goods_unit')->insert([
            [
                'id' => 1,
                'goods_id' => 100,
                'unit_id' => 11,
                'rate' => 1,
                'basic' => 1,
                'retailprice' => 100,
                'prebuyprice' => 80,
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
            $table->string('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->decimal('balance', 14, 4)->default(0);
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('erkai', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('customer_id')->index();
            $table->integer('department_id');
            $table->tinyInteger('type');
            $table->tinyInteger('status');
            $table->integer('medium_id');
            $table->decimal('payable', 14, 4)->default(0);
            $table->decimal('income', 14, 4)->default(0);
            $table->decimal('deposit', 14, 4)->default(0);
            $table->decimal('coupon', 14, 4)->default(0);
            $table->decimal('arrearage', 14, 4)->default(0);
            $table->text('remark')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('erkai_detail', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('erkai_id');
            $table->string('customer_id')->index();
            $table->tinyInteger('status');
            $table->string('type', 20);
            $table->integer('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->tinyInteger('splitable')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('goods_id')->nullable();
            $table->string('goods_name')->nullable();
            $table->integer('times');
            $table->integer('unit_id')->nullable();
            $table->string('unit_name', 10)->nullable();
            $table->string('specs')->nullable();
            $table->decimal('price', 14, 4);
            $table->decimal('sales_price', 14, 4);
            $table->decimal('payable', 14, 4);
            $table->decimal('amount', 14, 4)->default(0);
            $table->decimal('coupon', 14, 4)->default(0);
            $table->integer('department_id');
            $table->text('salesman');
            $table->text('remark')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('goods_unit', function (Blueprint $table): void {
            $table->id();
            $table->integer('goods_id');
            $table->integer('unit_id');
            $table->decimal('retailprice', 14, 4)->default(0);
            $table->decimal('prebuyprice', 14, 4)->default(0);
            $table->decimal('rate', 14, 4)->default(1);
            $table->tinyInteger('basic')->default(0);
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
