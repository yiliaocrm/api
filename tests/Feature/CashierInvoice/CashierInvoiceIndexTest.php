<?php

namespace Tests\Feature\CashierInvoice;

use App\Http\Controllers\Web\CashierInvoiceController;
use Database\Seeders\Tenant\SceneFields\CashierInvoiceSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierInvoiceIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('cashier-invoice/index', 'POST', CashierInvoiceController::class.'@index');
        Route::post('/cashier-invoice/index', [CashierInvoiceController::class, 'index']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('cashier_invoice_details');
        Schema::dropIfExists('cashier_invoices');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_index_route_exists_and_callable(): void
    {
        $payload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
        ]);

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function test_index_uses_default_ordering_by_created_at_desc(): void
    {
        $this->seedBasicData();

        $payload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['total']);
        $this->assertSame(2, $payload['data']['rows'][0]['id']);
        $this->assertSame(1, $payload['data']['rows'][1]['id']);
    }

    public function test_index_returns_customer_details_and_create_user_relation(): void
    {
        $this->seedBasicData();

        $payload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'sort' => 'cashier_invoices.created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['rows'][0]['id']);
        $this->assertSame('张三', $payload['data']['rows'][0]['customer']['name']);
        $this->assertSame('开票员甲', $payload['data']['rows'][0]['create_user']['name']);
        $this->assertCount(2, $payload['data']['rows'][0]['details']);
    }

    public function test_index_keyword_matches_name_phone_and_idcard(): void
    {
        $this->seedBasicData();

        $namePayload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'keyword' => '张三',
        ]);
        $phonePayload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'keyword' => '13800138000',
        ]);
        $idcardPayload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'keyword' => '510101199001011234',
        ]);

        $this->assertSame(200, $namePayload['code']);
        $this->assertSame(1, $namePayload['data']['total']);
        $this->assertSame(1, $namePayload['data']['rows'][0]['id']);

        $this->assertSame(200, $phonePayload['code']);
        $this->assertSame(1, $phonePayload['data']['total']);
        $this->assertSame(1, $phonePayload['data']['rows'][0]['id']);

        $this->assertSame(200, $idcardPayload['code']);
        $this->assertSame(1, $idcardPayload['data']['total']);
        $this->assertSame(1, $idcardPayload['data']['rows'][0]['id']);
    }

    public function test_index_filters_support_main_and_datetime_field(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $mainFieldPayload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'filters' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'invoice',
                ],
            ],
        ]);

        $datetimeFieldPayload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'filters' => [
                [
                    'field' => 'created_at',
                    'operator' => 'between',
                    'value' => ['2026-03-20', '2026-03-20'],
                ],
            ],
        ]);

        $this->assertSame(200, $mainFieldPayload['code']);
        $this->assertSame(1, $mainFieldPayload['data']['total']);
        $this->assertSame(2, $mainFieldPayload['data']['rows'][0]['id']);

        $this->assertSame(200, $datetimeFieldPayload['code']);
        $this->assertSame(1, $datetimeFieldPayload['data']['total']);
        $this->assertSame(2, $datetimeFieldPayload['data']['rows'][0]['id']);
    }

    public function test_index_rejects_customer_name_and_idcard_scene_filters_when_not_configured(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $customerNamePayload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'filters' => [
                [
                    'field' => 'customer.name',
                    'operator' => 'like',
                    'value' => '张三',
                ],
            ],
        ]);

        $customerIdcardPayload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'filters' => [
                [
                    'field' => 'customer.idcard',
                    'operator' => 'like',
                    'value' => '510101199001011234',
                ],
            ],
        ]);

        $this->assertSame(400, $customerNamePayload['code'] ?? null, json_encode($customerNamePayload, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('customer.name字段不在配置中', $customerNamePayload['msg'] ?? '');

        $this->assertSame(400, $customerIdcardPayload['code'] ?? null, json_encode($customerIdcardPayload, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('customer.idcard字段不在配置中', $customerIdcardPayload['msg'] ?? '');
    }

    public function test_index_rejects_malformed_between_filter_value_with_400(): void
    {
        $this->seedSceneFieldsForManage();
        $this->seedBasicData();

        $payload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
            'filters' => [
                [
                    'field' => 'created_at',
                    'operator' => 'between',
                    'value' => 'oops',
                ],
            ],
        ]);

        $this->assertNotSame(500, $payload['code'] ?? 500, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('区间操作值格式不正确', $payload['msg'] ?? '');
    }

    public function test_index_works_when_customer_table_has_no_phone_column(): void
    {
        Schema::dropIfExists('customer');
        $this->createCustomerTable(false);
        $this->seedBasicData(false);
        DB::connection()->enableQueryLog();

        $payload = $this->dispatchIndex([
            'rows' => 10,
            'page' => 1,
        ]);
        $queries = collect(DB::connection()->getQueryLog())->pluck('query');

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(2, $payload['data']['total']);
        $this->assertSame('李四', $payload['data']['rows'][0]['customer']['name']);
        $this->assertFalse(
            $queries->contains(fn (string $query) => str_contains(strtolower($query), 'select') && str_contains(strtolower($query), 'phone')),
            $queries->implode("\n")
        );
    }

    public function test_scene_field_seeder_contains_cashier_invoice_index(): void
    {
        if (! class_exists(CashierInvoiceSeeder::class)) {
            $this->fail('CashierInvoiceSeeder class not found');
        }

        $config = (new CashierInvoiceSeeder)->getConfig();
        $pages = array_unique(array_column($config, 'page'));

        $this->assertContains('CashierInvoiceIndex', $pages);

        $fields = array_column($config, 'field');
        $this->assertContains('type', $fields);
        $this->assertContains('key', $fields);
        $this->assertContains('date', $fields);
        $this->assertContains('tax_number', $fields);
        $this->assertContains('code', $fields);
        $this->assertContains('number', $fields);
        $this->assertContains('title', $fields);
        $this->assertContains('amount', $fields);
        $this->assertContains('create_user_id', $fields);
        $this->assertContains('remark', $fields);
        $this->assertContains('created_at', $fields);
        $this->assertContains('updated_at', $fields);

        $typeField = collect($config)->firstWhere('field', 'type');
        $this->assertSame('select', $typeField['component'] ?? null);
        $this->assertSame(
            [
                ['label' => '收据', 'value' => 'receipt'],
                ['label' => '发票', 'value' => 'invoice'],
            ],
            json_decode($typeField['component_params'] ?? '[]', true)['options'] ?? null
        );

        $customerNameField = collect($config)->firstWhere('field_alias', 'customer.name');
        $customerIdcardField = collect($config)->firstWhere('field_alias', 'customer.idcard');
        $this->assertNull($customerNameField);
        $this->assertNull($customerIdcardField);
    }

    private function dispatchIndex(array $data): array
    {
        $request = Request::create(
            '/cashier-invoice/index',
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
            (new CashierInvoiceSeeder)->getConfig()
        );

        DB::table('scene_fields')->insert($sceneFields);
    }

    private function seedBasicData(bool $includeCustomerPhone = true): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => '开票员甲'],
            ['id' => 2, 'name' => '开票员乙'],
        ]);

        $customers = [
            [
                'id' => 'customer-1',
                'name' => '张三',
                'idcard' => '510101199001011234',
                'keyword' => '张三,13800138000,510101199001011234',
                'created_at' => '2026-03-01 09:00:00',
                'updated_at' => '2026-03-01 09:00:00',
            ],
            [
                'id' => 'customer-2',
                'name' => '李四',
                'idcard' => '510101199201011234',
                'keyword' => '李四,13900139000,510101199201011234',
                'created_at' => '2026-03-01 09:00:00',
                'updated_at' => '2026-03-01 09:00:00',
            ],
        ];

        if ($includeCustomerPhone) {
            $customers[0]['phone'] = '13800138000';
            $customers[1]['phone'] = '13900139000';
        }

        DB::table('customer')->insert($customers);

        DB::table('cashier_invoices')->insert([
            [
                'id' => 1,
                'customer_id' => 'customer-1',
                'type' => 'receipt',
                'key' => 'KP202603100001',
                'date' => '2026-03-10',
                'code' => 'CODE-1',
                'number' => 'NO-1',
                'tax_number' => 'TAX-1',
                'title' => '抬头1',
                'bank_name' => '银行1',
                'bank_account' => '62220001',
                'create_user_id' => 1,
                'remark' => '备注1',
                'amount' => 100.0000,
                'created_at' => '2026-03-10 10:00:00',
                'updated_at' => '2026-03-10 10:00:00',
            ],
            [
                'id' => 2,
                'customer_id' => 'customer-2',
                'type' => 'invoice',
                'key' => 'KP202603200001',
                'date' => '2026-03-20',
                'code' => 'CODE-2',
                'number' => 'NO-2',
                'tax_number' => 'TAX-2',
                'title' => '抬头2',
                'bank_name' => '银行2',
                'bank_account' => '62220002',
                'create_user_id' => 2,
                'remark' => '备注2',
                'amount' => 220.0000,
                'created_at' => '2026-03-20 12:00:00',
                'updated_at' => '2026-03-20 12:00:00',
            ],
        ]);

        DB::table('cashier_invoice_details')->insert([
            [
                'id' => 1,
                'cashier_invoice_id' => 1,
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-1',
                'customer_product_id' => null,
                'customer_goods_id' => null,
                'package_id' => null,
                'package_name' => null,
                'product_id' => 1,
                'product_name' => '项目A',
                'goods_id' => null,
                'goods_name' => null,
                'name' => '开票项目A',
                'times' => 1,
                'unit_id' => null,
                'unit_name' => null,
                'specs' => null,
                'invoice_amount' => 40.0000,
                'income' => 40.0000,
                'deposit' => 0.0000,
                'created_at' => '2026-03-10 10:10:00',
                'updated_at' => '2026-03-10 10:10:00',
            ],
            [
                'id' => 2,
                'cashier_invoice_id' => 1,
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-2',
                'customer_product_id' => null,
                'customer_goods_id' => null,
                'package_id' => null,
                'package_name' => null,
                'product_id' => 2,
                'product_name' => '项目B',
                'goods_id' => null,
                'goods_name' => null,
                'name' => '开票项目B',
                'times' => 1,
                'unit_id' => null,
                'unit_name' => null,
                'specs' => null,
                'invoice_amount' => 60.0000,
                'income' => 60.0000,
                'deposit' => 0.0000,
                'created_at' => '2026-03-10 10:20:00',
                'updated_at' => '2026-03-10 10:20:00',
            ],
            [
                'id' => 3,
                'cashier_invoice_id' => 2,
                'customer_id' => 'customer-2',
                'cashier_id' => 'cashier-3',
                'customer_product_id' => null,
                'customer_goods_id' => null,
                'package_id' => null,
                'package_name' => null,
                'product_id' => 3,
                'product_name' => '项目C',
                'goods_id' => null,
                'goods_name' => null,
                'name' => '开票项目C',
                'times' => 1,
                'unit_id' => null,
                'unit_name' => null,
                'specs' => null,
                'invoice_amount' => 220.0000,
                'income' => 220.0000,
                'deposit' => 0.0000,
                'created_at' => '2026-03-20 12:10:00',
                'updated_at' => '2026-03-20 12:10:00',
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

        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        $this->createCustomerTable();

        Schema::create('cashier_invoices', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->uuid('customer_id')->index();
            $table->string('type');
            $table->string('key');
            $table->date('date');
            $table->string('code')->nullable();
            $table->string('number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('title')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->integer('create_user_id');
            $table->text('remark')->nullable();
            $table->decimal('amount', 14, 4);
            $table->timestamps();
        });

        Schema::create('cashier_invoice_details', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('cashier_invoice_id')->index();
            $table->uuid('customer_id')->index();
            $table->uuid('cashier_id')->index();
            $table->uuid('customer_product_id')->nullable();
            $table->uuid('customer_goods_id')->nullable();
            $table->integer('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('goods_id')->nullable();
            $table->string('goods_name')->nullable();
            $table->string('name');
            $table->unsignedInteger('times');
            $table->unsignedInteger('unit_id')->nullable();
            $table->string('unit_name', 10)->nullable();
            $table->string('specs')->nullable();
            $table->decimal('invoice_amount', 14, 4);
            $table->decimal('income', 14, 4);
            $table->decimal('deposit', 14, 4);
            $table->timestamps();
        });
    }

    private function createCustomerTable(bool $includePhone = true): void
    {
        Schema::create('customer', function (Blueprint $table) use ($includePhone): void {
            $table->uuid('id')->primary();
            $table->string('name');
            if ($includePhone) {
                $table->string('phone')->nullable();
            }
            $table->string('idcard')->nullable();
            $table->string('keyword');
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
