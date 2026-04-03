<?php

namespace Tests\Feature\CashierRefund;

use App\Http\Controllers\Web\CashierRefundController;
use Database\Seeders\Tenant\SceneFields\CashierRefundSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierRefundManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('cashier-refund/manage', 'POST', CashierRefundController::class.'@manage');
        Route::post('/cashier-refund/manage', [CashierRefundController::class, 'manage']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('cashier_refund_detail');
        Schema::dropIfExists('cashier_refund');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('users');
        Schema::dropIfExists('department');

        parent::tearDown();
    }

    public function test_manage_requires_date_validation(): void
    {
        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
        ]);

        $this->assertSame(400, $payload['code']);
        $this->assertStringContainsString('查询日期', $payload['msg']);
    }

    public function test_manage_rejects_invalid_order_validation(): void
    {
        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'order' => 'invalid-order',
        ]);

        $this->assertSame(400, $payload['code']);
        $this->assertStringContainsString('排序方式', $payload['msg']);
    }

    public function test_manage_rejects_invalid_rows_validation(): void
    {
        $payload = $this->dispatchManage([
            'rows' => 0,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
        ]);

        $this->assertSame(400, $payload['code']);
        $this->assertStringContainsString('每页数量', $payload['msg']);
    }

    public function test_manage_rejects_invalid_filters_validation(): void
    {
        $this->seedSceneFieldsForManage();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'invalid_field',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
        ]);

        $this->assertSame(400, $payload['code']);
        $this->assertStringContainsString('字段不在配置中', $payload['msg']);
    }

    public function test_manage_uses_default_ordering_by_created_at_desc(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['total']);
        $this->assertSame('refund-2', $payload['data']['rows'][0]['id']);
        $this->assertSame('refund-1', $payload['data']['rows'][1]['id']);
    }

    public function test_manage_returns_customer_user_and_details_department(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'sort' => 'cashier_refund.created_at',
            'order' => 'asc',
            'date' => ['2026-03-01', '2026-03-31'],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame('refund-1', $payload['data']['rows'][0]['id']);
        $this->assertSame('客户A', $payload['data']['rows'][0]['customer']['name']);
        $this->assertSame('收银员甲', $payload['data']['rows'][0]['user']['name']);
        $this->assertSame(1, $payload['data']['rows'][0]['status']);
        $this->assertSame('待审核', $payload['data']['rows'][0]['status_text']);
        $this->assertCount(2, $payload['data']['rows'][0]['details']);
        $this->assertSame(1, $payload['data']['rows'][0]['details'][0]['id']);
        $this->assertSame(2, $payload['data']['rows'][0]['details'][1]['id']);
        $this->assertSame('一科', $payload['data']['rows'][0]['details'][0]['department']['name']);
    }

    public function test_manage_filters_by_customer_keyword(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'keyword' => '目标',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('refund-1', $payload['data']['rows'][0]['id']);
    }

    public function test_manage_filters_by_scene_status(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 2,
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('refund-2', $payload['data']['rows'][0]['id']);
    }

    public function test_manage_filters_by_scene_id(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'id',
                    'operator' => '=',
                    'value' => 'refund-1',
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('refund-1', $payload['data']['rows'][0]['id']);
    }

    public function test_manage_filters_by_scene_amount(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'amount',
                    'operator' => '=',
                    'value' => 200,
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('refund-2', $payload['data']['rows'][0]['id']);
    }

    public function test_manage_filters_by_scene_user_id(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'user_id',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('refund-1', $payload['data']['rows'][0]['id']);
    }

    public function test_manage_filters_detail_remark_without_duplicate_rows(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'remark',
                    'operator' => 'like',
                    'value' => '重复关键字',
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertCount(1, $payload['data']['rows']);
        $this->assertSame('refund-1', $payload['data']['rows'][0]['id']);
    }

    public function test_manage_filters_by_main_refund_remark_when_detail_remark_not_match(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'remark',
                    'operator' => 'like',
                    'value' => '主单备注2',
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('refund-2', $payload['data']['rows'][0]['id']);
    }

    public function test_manage_rejects_malformed_between_filter_value_before_query_execution(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'amount',
                    'operator' => 'between',
                    'value' => 'oops',
                ],
            ],
        ]);

        $this->assertNotSame(500, $payload['code'] ?? 500, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('区间操作值格式不正确', $payload['msg'] ?? '');
    }

    public function test_scene_field_seeder_contains_cashier_refund_index(): void
    {
        if (! class_exists(CashierRefundSeeder::class)) {
            $this->fail('CashierRefundSeeder class not found');
        }

        $config = (new CashierRefundSeeder)->getConfig();
        $pages = array_unique(array_column($config, 'page'));

        $this->assertContains('CashierRefundIndex', $pages);
        $fields = array_column($config, 'field');
        $this->assertContains('status', $fields);
        $this->assertContains('id', $fields);
        $this->assertContains('amount', $fields);
        $this->assertContains('user_id', $fields);
        $this->assertContains('created_at', $fields);
        $this->assertContains('remark', $fields);

        $remarkConfig = collect($config)->firstWhere('field', 'remark');
        $this->assertNotNull($remarkConfig['query_config'] ?? null);
        $this->assertStringContainsString('cashier_refund.remark', (string) $remarkConfig['query_config']);
        $this->assertStringContainsString('cashier_refund_detail.remark', (string) $remarkConfig['query_config']);
    }

    private function dispatchManage(array $data): array
    {
        $request = Request::create(
            '/cashier-refund/manage',
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

    private function seedSceneFieldsForManage(): void
    {
        $sceneFields = array_map(
            static function (array $field): array {
                $field['field_alias'] = $field['field_alias'] ?? null;
                $field['query_config'] = $field['query_config'] ?? null;
                $field['api'] = $field['api'] ?? null;
                $field['component_params'] = $field['component_params'] ?? null;
                $field['keyword'] = $field['name'];

                return $field;
            },
            (new CashierRefundSeeder)->getConfig()
        );

        DB::table('scene_fields')->insert($sceneFields);
    }

    private function seedBasicData(): void
    {
        $this->seedDepartments();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedRefunds();
        $this->seedRefundDetails();
    }

    private function seedDepartments(): void
    {
        DB::table('department')->insert([
            ['id' => 1, 'name' => '一科'],
            ['id' => 2, 'name' => '二科'],
        ]);
    }

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => '收银员甲'],
            ['id' => 2, 'name' => '收银员乙'],
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

    private function seedRefunds(): void
    {
        DB::table('cashier_refund')->insert([
            [
                'id' => 'refund-1',
                'customer_id' => 'customer-1',
                'cashier_id' => null,
                'amount' => 100.0000,
                'remark' => '主单备注1',
                'detail' => json_encode([], JSON_THROW_ON_ERROR),
                'status' => 1,
                'user_id' => 1,
                'created_at' => '2026-03-10 10:00:00',
                'updated_at' => '2026-03-10 10:00:00',
            ],
            [
                'id' => 'refund-2',
                'customer_id' => 'customer-2',
                'cashier_id' => null,
                'amount' => 200.0000,
                'remark' => '主单备注2',
                'detail' => json_encode([], JSON_THROW_ON_ERROR),
                'status' => 2,
                'user_id' => 2,
                'created_at' => '2026-03-20 12:00:00',
                'updated_at' => '2026-03-20 12:00:00',
            ],
            [
                'id' => 'refund-out',
                'customer_id' => 'customer-1',
                'cashier_id' => null,
                'amount' => 300.0000,
                'remark' => '区间外数据',
                'detail' => json_encode([], JSON_THROW_ON_ERROR),
                'status' => 1,
                'user_id' => 1,
                'created_at' => '2026-02-20 12:00:00',
                'updated_at' => '2026-02-20 12:00:00',
            ],
        ]);
    }

    private function seedRefundDetails(): void
    {
        DB::table('cashier_refund_detail')->insert([
            [
                'id' => 1,
                'status' => 1,
                'cashier_refund_id' => 'refund-1',
                'cashier_id' => null,
                'customer_id' => 'customer-1',
                'customer_product_id' => null,
                'customer_goods_id' => null,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'department_id' => 1,
                'amount' => 30.0000,
                'salesman' => json_encode([['id' => 1, 'name' => '销售A']], JSON_THROW_ON_ERROR),
                'user_id' => 1,
                'cashier_user_id' => null,
                'remark' => '重复关键字-一',
                'created_at' => '2026-03-10 10:30:00',
                'updated_at' => '2026-03-10 10:30:00',
            ],
            [
                'id' => 2,
                'status' => 1,
                'cashier_refund_id' => 'refund-1',
                'cashier_id' => null,
                'customer_id' => 'customer-1',
                'customer_product_id' => null,
                'customer_goods_id' => null,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'department_id' => 2,
                'amount' => 70.0000,
                'salesman' => json_encode([['id' => 2, 'name' => '销售B']], JSON_THROW_ON_ERROR),
                'user_id' => 1,
                'cashier_user_id' => null,
                'remark' => '重复关键字-二',
                'created_at' => '2026-03-10 11:30:00',
                'updated_at' => '2026-03-10 11:30:00',
            ],
            [
                'id' => 3,
                'status' => 2,
                'cashier_refund_id' => 'refund-2',
                'cashier_id' => null,
                'customer_id' => 'customer-2',
                'customer_product_id' => null,
                'customer_goods_id' => null,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'department_id' => 1,
                'amount' => 200.0000,
                'salesman' => json_encode([['id' => 2, 'name' => '销售B']], JSON_THROW_ON_ERROR),
                'user_id' => 2,
                'cashier_user_id' => null,
                'remark' => '普通备注',
                'created_at' => '2026-03-20 12:30:00',
                'updated_at' => '2026-03-20 12:30:00',
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

        Schema::create('department', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->decimal('balance', 14, 4)->default(0);
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('cashier_refund', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->uuid('cashier_id')->nullable();
            $table->decimal('amount', 14, 4);
            $table->text('remark')->nullable();
            $table->text('detail');
            $table->tinyInteger('status');
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('cashier_refund_detail', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->tinyInteger('status');
            $table->uuid('cashier_refund_id');
            $table->uuid('cashier_id')->nullable();
            $table->uuid('customer_id')->index();
            $table->uuid('customer_product_id')->nullable();
            $table->uuid('customer_goods_id')->nullable();
            $table->integer('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('goods_id')->nullable();
            $table->string('goods_name')->nullable();
            $table->integer('times');
            $table->integer('unit_id')->nullable();
            $table->string('specs')->nullable();
            $table->integer('department_id');
            $table->decimal('amount', 14, 4);
            $table->text('salesman')->nullable();
            $table->integer('user_id');
            $table->integer('cashier_user_id')->nullable();
            $table->text('remark')->nullable();
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
