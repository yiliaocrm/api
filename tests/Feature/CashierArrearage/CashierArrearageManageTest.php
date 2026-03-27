<?php

namespace Tests\Feature\CashierArrearage;

use App\Http\Controllers\Web\CashierArrearageController;
use Database\Seeders\Tenant\SceneFields\CashierArrearageSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierArrearageManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('cashier-arrearage/manage', 'POST', CashierArrearageController::class.'@manage');
        Route::post('/cashier-arrearage/manage', [CashierArrearageController::class, 'manage']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('cashier_arrearage_detail');
        Schema::dropIfExists('cashier_arrearage');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    public function test_manage_lists_cashier_arrearages_with_customer_and_ordered_details(): void
    {
        $this->seedSceneFields();
        $this->seedCustomers();
        $this->seedArrearages();
        $this->seedArrearageDetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'sort' => 'cashier_arrearage.created_at',
            'order' => 'desc',
            'date' => ['2026-03-01', '2026-03-31'],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertCount(1, $payload['data']['rows']);
        $this->assertSame('arrearage-1', $payload['data']['rows'][0]['id']);
        $this->assertSame('客户A', $payload['data']['rows'][0]['customer']['name']);
        $this->assertSame(2, $payload['data']['rows'][0]['details'][0]['id']);
        $this->assertSame(1, $payload['data']['rows'][0]['details'][1]['id']);
    }

    public function test_manage_requires_date_validation(): void
    {
        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'sort' => 'cashier_arrearage.created_at',
            'order' => 'desc',
        ]);

        $this->assertSame(400, $payload['code']);
        $this->assertStringContainsString('查询日期', $payload['msg']);
    }

    public function test_manage_filters_by_keyword(): void
    {
        $this->seedSceneFields();
        $this->seedCustomers();
        $this->seedKeywordArrearages();
        $this->seedKeywordDetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'sort' => 'cashier_arrearage.created_at',
            'order' => 'desc',
            'date' => ['2026-03-01', '2026-03-31'],
            'keyword' => '目标',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertCount(1, $payload['data']['rows']);
        $this->assertSame('arrearage-keyword-1', $payload['data']['rows'][0]['id']);
        $this->assertSame('客户A', $payload['data']['rows'][0]['customer']['name']);
    }

    public function test_manage_filters_by_scene_status(): void
    {
        $this->seedSceneFields();
        $this->seedCustomers();
        $this->seedStatusArrearages();
        $this->seedStatusDetails();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'sort' => 'cashier_arrearage.created_at',
            'order' => 'desc',
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertCount(1, $payload['data']['rows']);
        $this->assertSame('arrearage-status-1', $payload['data']['rows'][0]['id']);
        $this->assertSame(1, $payload['data']['rows'][0]['status']);
    }

    public function test_scene_field_seeder_contains_product_and_package_filters(): void
    {
        $config = (new CashierArrearageSeeder)->getConfig();
        $fields = array_column($config, 'field');

        $this->assertContains('product_name', $fields);
        $this->assertContains('package_name', $fields);
    }

    private function dispatchManage(array $data): array
    {
        $request = Request::create(
            '/cashier-arrearage/manage',
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
                $field['field_alias'] = $field['field_alias'] ?? null;
                $field['query_config'] = $field['query_config'] ?? null;
                $field['api'] = $field['api'] ?? null;
                $field['component_params'] = $field['component_params'] ?? null;
                $field['keyword'] = implode(',', parse_pinyin($field['name']));

                return $field;
            },
            (new CashierArrearageSeeder)->getConfig()
        );

        DB::table('scene_fields')->insert([
            [
                'page' => 'CashierArrearageIndex',
                'name' => '关键词',
                'table' => 'customer',
                'field' => 'keyword',
                'api' => null,
                'field_alias' => 'keyword',
                'field_type' => 'string',
                'component' => 'input',
                'component_params' => null,
                'operators' => json_encode(['like'], JSON_THROW_ON_ERROR),
                'query_config' => null,
                'keyword' => '关键词',
            ],
            ...$sceneFields,
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

    private function seedArrearages(): void
    {
        DB::table('cashier_arrearage')->insert([
            [
                'id' => 'arrearage-1',
                'cashier_id' => 'cashier-1',
                'customer_id' => 'customer-1',
                'status' => 1,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'table_id' => 'table-1',
                'payable' => 120.0000,
                'income' => 20.0000,
                'arrearage' => 100.0000,
                'amount' => 20.0000,
                'leftover' => 100.0000,
                'salesman' => json_encode([['id' => 1, 'name' => '销售A']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'last_repayment_time' => '2026-03-20 10:00:00',
                'user_id' => 1,
                'created_at' => '2026-03-20 10:00:00',
                'updated_at' => '2026-03-20 10:00:00',
            ],
            [
                'id' => 'arrearage-2',
                'cashier_id' => 'cashier-2',
                'customer_id' => 'customer-2',
                'status' => 1,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'table_id' => 'table-2',
                'payable' => 200.0000,
                'income' => 0.0000,
                'arrearage' => 200.0000,
                'amount' => 0.0000,
                'leftover' => 200.0000,
                'salesman' => json_encode([['id' => 2, 'name' => '销售B']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'last_repayment_time' => '2026-02-20 10:00:00',
                'user_id' => 1,
                'created_at' => '2026-02-20 10:00:00',
                'updated_at' => '2026-02-20 10:00:00',
            ],
        ]);
    }

    private function seedArrearageDetails(): void
    {
        DB::table('cashier_arrearage_detail')->insert([
            [
                'id' => 1,
                'cashier_arrearage_id' => 'arrearage-1',
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-1',
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'income' => 30.0000,
                'remark' => '先写入的明细',
                'salesman' => json_encode([['id' => 1, 'name' => '销售A']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'user_id' => 1,
                'created_at' => '2026-03-20 10:00:00',
                'updated_at' => '2026-03-20 10:00:00',
            ],
            [
                'id' => 2,
                'cashier_arrearage_id' => 'arrearage-1',
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-1',
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'income' => 40.0000,
                'remark' => '后写入的明细',
                'salesman' => json_encode([['id' => 1, 'name' => '销售A']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'user_id' => 1,
                'created_at' => '2026-03-20 11:00:00',
                'updated_at' => '2026-03-20 11:00:00',
            ],
        ]);
    }

    private function seedKeywordArrearages(): void
    {
        DB::table('cashier_arrearage')->insert([
            [
                'id' => 'arrearage-keyword-1',
                'cashier_id' => 'cashier-keyword-1',
                'customer_id' => 'customer-1',
                'status' => 1,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'table_id' => 'table-keyword-1',
                'payable' => 50.0000,
                'income' => 0.0000,
                'arrearage' => 50.0000,
                'amount' => 0.0000,
                'leftover' => 50.0000,
                'salesman' => json_encode([['id' => 1, 'name' => '销售A']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'last_repayment_time' => null,
                'user_id' => 1,
                'created_at' => '2026-03-18 10:00:00',
                'updated_at' => '2026-03-18 10:00:00',
            ],
            [
                'id' => 'arrearage-keyword-2',
                'cashier_id' => 'cashier-keyword-2',
                'customer_id' => 'customer-2',
                'status' => 1,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'table_id' => 'table-keyword-2',
                'payable' => 60.0000,
                'income' => 0.0000,
                'arrearage' => 60.0000,
                'amount' => 0.0000,
                'leftover' => 60.0000,
                'salesman' => json_encode([['id' => 2, 'name' => '销售B']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'last_repayment_time' => null,
                'user_id' => 1,
                'created_at' => '2026-03-17 10:00:00',
                'updated_at' => '2026-03-17 10:00:00',
            ],
        ]);
    }

    private function seedKeywordDetails(): void
    {
        DB::table('cashier_arrearage_detail')->insert([
            [
                'id' => 11,
                'cashier_arrearage_id' => 'arrearage-keyword-1',
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-keyword-1',
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'income' => 10.0000,
                'remark' => null,
                'salesman' => json_encode([['id' => 1, 'name' => '销售A']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'user_id' => 1,
                'created_at' => '2026-03-18 10:00:00',
                'updated_at' => '2026-03-18 10:00:00',
            ],
            [
                'id' => 12,
                'cashier_arrearage_id' => 'arrearage-keyword-2',
                'customer_id' => 'customer-2',
                'cashier_id' => 'cashier-keyword-2',
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'income' => 20.0000,
                'remark' => null,
                'salesman' => json_encode([['id' => 2, 'name' => '销售B']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'user_id' => 1,
                'created_at' => '2026-03-17 10:00:00',
                'updated_at' => '2026-03-17 10:00:00',
            ],
        ]);
    }

    private function seedStatusArrearages(): void
    {
        DB::table('cashier_arrearage')->insert([
            [
                'id' => 'arrearage-status-1',
                'cashier_id' => 'cashier-status-1',
                'customer_id' => 'customer-1',
                'status' => 1,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'table_id' => 'table-status-1',
                'payable' => 70.0000,
                'income' => 0.0000,
                'arrearage' => 70.0000,
                'amount' => 0.0000,
                'leftover' => 70.0000,
                'salesman' => json_encode([['id' => 1, 'name' => '销售A']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'last_repayment_time' => null,
                'user_id' => 1,
                'created_at' => '2026-03-16 10:00:00',
                'updated_at' => '2026-03-16 10:00:00',
            ],
            [
                'id' => 'arrearage-status-2',
                'cashier_id' => 'cashier-status-2',
                'customer_id' => 'customer-2',
                'status' => 2,
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'table_id' => 'table-status-2',
                'payable' => 80.0000,
                'income' => 80.0000,
                'arrearage' => 0.0000,
                'amount' => 80.0000,
                'leftover' => 0.0000,
                'salesman' => json_encode([['id' => 2, 'name' => '销售B']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'last_repayment_time' => '2026-03-15 10:00:00',
                'user_id' => 1,
                'created_at' => '2026-03-15 10:00:00',
                'updated_at' => '2026-03-15 10:00:00',
            ],
        ]);
    }

    private function seedStatusDetails(): void
    {
        DB::table('cashier_arrearage_detail')->insert([
            [
                'id' => 21,
                'cashier_arrearage_id' => 'arrearage-status-1',
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-status-1',
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'income' => 5.0000,
                'remark' => null,
                'salesman' => json_encode([['id' => 1, 'name' => '销售A']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'user_id' => 1,
                'created_at' => '2026-03-16 10:00:00',
                'updated_at' => '2026-03-16 10:00:00',
            ],
            [
                'id' => 22,
                'cashier_arrearage_id' => 'arrearage-status-2',
                'customer_id' => 'customer-2',
                'cashier_id' => 'cashier-status-2',
                'package_id' => null,
                'package_name' => null,
                'product_id' => null,
                'product_name' => null,
                'goods_id' => null,
                'goods_name' => null,
                'times' => 1,
                'unit_id' => null,
                'specs' => null,
                'income' => 10.0000,
                'remark' => null,
                'salesman' => json_encode([['id' => 2, 'name' => '销售B']], JSON_THROW_ON_ERROR),
                'department_id' => 1,
                'user_id' => 1,
                'created_at' => '2026-03-15 10:00:00',
                'updated_at' => '2026-03-15 10:00:00',
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
            $table->decimal('balance', 14, 4)->default(0);
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('cashier_arrearage', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('cashier_id')->index();
            $table->uuid('customer_id')->index();
            $table->tinyInteger('status')->default(1);
            $table->integer('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('goods_id')->nullable();
            $table->string('goods_name')->nullable();
            $table->integer('times');
            $table->integer('unit_id')->nullable();
            $table->string('specs')->nullable();
            $table->uuid('table_id')->index();
            $table->decimal('payable', 14, 4);
            $table->decimal('income', 14, 4);
            $table->decimal('arrearage', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->decimal('leftover', 14, 4)->default(0);
            $table->text('salesman')->nullable();
            $table->integer('department_id');
            $table->dateTime('last_repayment_time')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('cashier_arrearage_detail', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('cashier_arrearage_id');
            $table->uuid('customer_id')->index();
            $table->uuid('cashier_id')->index();
            $table->integer('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('goods_id')->nullable();
            $table->string('goods_name')->nullable();
            $table->integer('times');
            $table->integer('unit_id')->nullable();
            $table->string('specs')->nullable();
            $table->decimal('income', 14, 4);
            $table->text('remark')->nullable();
            $table->text('salesman')->nullable();
            $table->integer('department_id');
            $table->integer('user_id');
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
