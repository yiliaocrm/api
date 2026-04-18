<?php

namespace Tests\Feature\CashierRetail;

use App\Enums\CashierRetailStatus;
use App\Http\Controllers\Web\CashierRetailController;
use App\Http\Requests\Web\CashierRetailRequest;
use Database\Seeders\Tenant\SceneFields\CashierRetailSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashierRetailRequestTest extends TestCase
{
    private string $customerId;

    private string $cashierRetailId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerId = (string) Str::uuid();
        $this->cashierRetailId = (string) Str::uuid();

        $this->assertApplicationRouteExists(
            'cashier-retail/manage',
            'POST',
            'App\Http\Controllers\Web\CashierRetailController@manage'
        );

        $this->dropFixtureTables();
        $this->createSceneFieldsTable();
        $this->createCashierRetailDependencyTables();
        $this->seedSceneFields();
        $this->seedCashierRetailDependencies();
    }

    protected function tearDown(): void
    {
        $this->dropFixtureTables();

        parent::tearDown();
    }

    public function test_manage_method_uses_web_cashier_retail_request(): void
    {
        $method = new \ReflectionMethod(CashierRetailController::class, 'manage');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame(CashierRetailRequest::class, $parameters[0]->getType()?->getName());
    }

    public function test_manage_reads_filters_and_pagination_from_request_input(): void
    {
        $controller = new \ReflectionClass(CashierRetailController::class);
        $source = file_get_contents($controller->getFileName());

        $this->assertIsString($source);
        $this->assertStringContainsString("\$request->input('rows'", $source);
        $this->assertStringContainsString("\$request->input('sort'", $source);
        $this->assertStringContainsString("\$request->input('order'", $source);
        $this->assertStringContainsString("\$request->input('keyword'", $source);
        $this->assertStringContainsString("\$request->input('date'", $source);
        $this->assertStringContainsString("\$request->input('filters'", $source);
    }

    public function test_manage_requires_date_range(): void
    {
        $validator = $this->makeManageValidator([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }

    public function test_manage_rejects_invalid_filters_payload(): void
    {
        $validator = $this->makeManageValidator([
            'date' => ['2026-01-01', '2026-01-31'],
            'filters' => [
                [
                    'field' => null,
                    'operator' => 123,
                ],
            ],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('filters.0.field', $validator->errors()->toArray());
        $this->assertArrayHasKey('filters.0.operator', $validator->errors()->toArray());
    }

    public function test_manage_rejects_non_array_filter_element_without_crashing(): void
    {
        $validator = $this->makeManageValidator([
            'date' => ['2026-01-01', '2026-01-31'],
            'filters' => ['oops'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('filters.0', $validator->errors()->toArray());
    }

    public function test_manage_rejects_invalid_sort_column(): void
    {
        $validator = $this->makeManageValidator([
            'date' => ['2026-01-01', '2026-01-31'],
            'sort' => 'invalid_column',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sort', $validator->errors()->toArray());
    }

    public function test_manage_allows_between_filter_without_range_value(): void
    {
        $validator = $this->makeManageValidator([
            'date' => ['2026-01-01', '2026-01-31'],
            'filters' => [
                [
                    'field' => 'created_at',
                    'operator' => 'between',
                    'value' => 'oops',
                ],
            ],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_manage_accepts_not_equal_angle_operator_when_configured(): void
    {
        $validator = $this->makeManageValidator([
            'date' => ['2026-01-01', '2026-01-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '<>',
                    'value' => 1,
                ],
            ],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_manage_accepts_medium_id_equal_filter_with_array_path_value(): void
    {
        $validator = $this->makeManageValidator([
            'date' => ['2026-01-01', '2026-01-31'],
            'filters' => [
                [
                    'field' => 'medium_id',
                    'operator' => '=',
                    'value' => [10, 20],
                ],
            ],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_manage_allows_array_value_for_regular_equal_filter(): void
    {
        $validator = $this->makeManageValidator([
            'date' => ['2026-01-01', '2026-01-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => [1, 2],
                ],
            ],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_manage_allows_custom_string_operator_without_scene_operator_validation(): void
    {
        $validator = $this->makeManageValidator([
            'date' => ['2026-01-01', '2026-01-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => 'custom-operator',
                    'value' => '1',
                ],
            ],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_charge_accepts_minimal_valid_payload(): void
    {
        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $this->makeChargePayload()
        );

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_charge_rejects_duplicate_pay_accounts(): void
    {
        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $this->makeChargePayload([
                'pay' => [
                    [
                        'accounts_id' => 2,
                        'income' => 50,
                    ],
                    [
                        'accounts_id' => 2,
                        'income' => 30,
                    ],
                ],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertContains('收款账户不能重复!', $validator->errors()->get('customer_id'));
    }

    public function test_charge_rejects_non_pending_retail_id(): void
    {
        $dealId = (string) Str::uuid();
        DB::table('cashier_retail')->insert([
            'id' => $dealId,
            'status' => CashierRetailStatus::DEAL->value,
        ]);

        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $this->makeChargePayload([
                'id' => $dealId,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertSame('订单状态错误!', $validator->errors()->first('id'));
    }

    public function test_charge_rejects_duplicate_deposit_product_rows(): void
    {
        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $this->makeChargePayload([
                'detail' => [
                    [
                        'product_id' => 1,
                        'payable' => 30,
                    ],
                    [
                        'product_id' => 1,
                        'payable' => 20,
                    ],
                ],
                'pay' => [
                    [
                        'accounts_id' => 2,
                        'income' => 100,
                    ],
                ],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertContains('【预收费用】重复!', $validator->errors()->get('customer_id'));
    }

    public function test_charge_requires_pay_rows_when_deposit_product_exists(): void
    {
        $payload = $this->makeChargePayload([
            'detail' => [
                [
                    'product_id' => 1,
                    'payable' => 80,
                ],
            ],
        ]);
        $payload['pay'] = [];

        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $payload
        );

        $this->assertTrue($validator->fails());
        $this->assertContains('【预收费用】项目必须收费!', $validator->errors()->get('customer_id'));
    }

    public function test_charge_requires_non_balance_income_to_cover_deposit_product(): void
    {
        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $this->makeChargePayload([
                'detail' => [
                    [
                        'product_id' => 1,
                        'payable' => 200,
                    ],
                ],
                'pay' => [
                    [
                        'accounts_id' => 1,
                        'income' => 200,
                    ],
                ],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertContains('【实收金额】必须大于【预收费用】!', $validator->errors()->get('customer_id'));
    }

    public function test_charge_rejects_balance_payment_exceeding_customer_balance(): void
    {
        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $this->makeChargePayload([
                'detail' => [
                    [
                        'product_id' => 2,
                        'payable' => 1001,
                    ],
                ],
                'pay' => [
                    [
                        'accounts_id' => 1,
                        'income' => 1001,
                    ],
                ],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertContains('账户余额不够支付', $validator->errors()->get('customer_id'));
    }

    public function test_charge_requires_customer_id(): void
    {
        $payload = $this->makeChargePayload([
            'medium_id' => 1,
        ]);
        unset($payload['customer_id']);

        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $payload
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
    }

    public function test_charge_requires_existing_medium(): void
    {
        $validator = $this->makeActionValidator(
            'charge',
            'POST',
            $this->makeChargePayload([
                'medium_id' => 999,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('medium_id', $validator->errors()->toArray());
    }

    public function test_pending_requires_customer_medium_and_type(): void
    {
        $validator = $this->makeActionValidator('pending', 'POST', []);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('medium_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_remove_rejects_missing_id(): void
    {
        $validator = $this->makeActionValidator('remove', 'GET', []);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('id', $validator->errors()->toArray());
    }

    public function test_remove_rejects_deal_status_records(): void
    {
        $dealId = (string) Str::uuid();
        DB::table('cashier_retail')->insert([
            'id' => $dealId,
            'status' => CashierRetailStatus::DEAL->value,
        ]);

        $validator = $this->makeActionValidator('remove', 'GET', ['id' => $dealId]);

        $this->assertTrue($validator->fails());
        $this->assertSame('零售单已收费,无法删除!', $validator->errors()->first('id'));
    }

    public function test_remove_allows_pending_status_record(): void
    {
        $validator = $this->makeActionValidator('remove', 'GET', ['id' => $this->cashierRetailId]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_info_requires_existing_id(): void
    {
        $validator = $this->makeActionValidator(
            'info',
            'GET',
            ['id' => (string) Str::uuid()]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame('没有找到对应的零售单!', $validator->errors()->first('id'));
    }

    public function test_cashier_retail_seeder_uses_current_table_prefix_in_medium_query_config(): void
    {
        $connection = DB::connection();
        $originalPrefix = $connection->getTablePrefix();
        $connection->setTablePrefix('tenant_');

        try {
            $config = (new CashierRetailSeeder)->getConfig();
            $mediumField = collect($config)->firstWhere('field', 'medium_id');
            $queryConfig = json_decode($mediumField['query_config'] ?? '[]', true, 512, JSON_THROW_ON_ERROR);
            $sql = collect($queryConfig)
                ->pluck('wheres')
                ->flatten(1)
                ->pluck('sql')
                ->implode("\n");

            $this->assertStringContainsString('tenant_cashier_retail.medium_id', $sql);
            $this->assertStringContainsString('tenant_medium', $sql);
            $this->assertStringNotContainsString('cy_cashier_retail', $sql);
            $this->assertStringNotContainsString('cy_medium', $sql);
        } finally {
            $connection->setTablePrefix($originalPrefix);
        }
    }

    public function test_cashier_retail_seeder_uses_enum_options_for_status_field(): void
    {
        $config = (new CashierRetailSeeder)->getConfig();
        $statusField = collect($config)->firstWhere('field', 'status');
        $componentParams = json_decode($statusField['component_params'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $options = collect($componentParams['options'] ?? [])
            ->mapWithKeys(fn (array $item) => [$item['value'] => $item['label']])
            ->all();

        $this->assertSame(CashierRetailStatus::options(), $options);
    }

    private function makeManageValidator(array $payload)
    {
        $request = CashierRetailRequest::create('/cashier-retail/manage', 'POST', $payload);
        $request->setRouteResolver(fn () => Route::getRoutes()->match(Request::create('/cashier-retail/manage', 'POST')));
        app()->instance('request', $request);

        $validator = validator()->make($request->all(), $request->rules(), $request->messages());

        return $validator;
    }

    private function makeActionValidator(string $action, string $method, array $payload)
    {
        $uri = sprintf('/cashier-retail/%s', $action);
        $request = CashierRetailRequest::create($uri, $method, $payload);
        $request->setRouteResolver(fn () => Route::getRoutes()->match(Request::create($uri, $method)));
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        app()->instance('request', $request);

        $prepareForValidation = new \ReflectionMethod($request, 'prepareForValidation');
        $prepareForValidation->setAccessible(true);
        $prepareForValidation->invoke($request);

        $passesAuthorization = new \ReflectionMethod($request, 'passesAuthorization');
        $passesAuthorization->setAccessible(true);
        if ($passesAuthorization->invoke($request) === false) {
            $failedAuthorization = new \ReflectionMethod($request, 'failedAuthorization');
            $failedAuthorization->setAccessible(true);
            $failedAuthorization->invoke($request);
        }

        $getValidatorInstance = new \ReflectionMethod($request, 'getValidatorInstance');
        $getValidatorInstance->setAccessible(true);

        return $getValidatorInstance->invoke($request);
    }

    // charge 场景下用于请求校验的最小可通过载荷，非控制器/详情完整业务夹具。
    private function makeChargePayload(array $overrides = []): array
    {
        $payload = [
            'customer_id' => $this->customerId,
            'medium_id' => 1,
            'type' => 'retail',
            'detail' => [
                [
                    'product_id' => 2,
                    'payable' => 100,
                ],
            ],
            'pay' => [
                [
                    'accounts_id' => 2,
                    'income' => 100,
                ],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }

    private function createSceneFieldsTable(): void
    {
        Schema::create('scene_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('page')->nullable();
            $table->string('field')->nullable();
            $table->string('field_alias')->nullable();
            $table->text('operators')->nullable();
        });
    }

    private function createCashierRetailDependencyTables(): void
    {
        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->decimal('balance', 10, 2)->default(0);
        });

        Schema::create('medium', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('cashier_retail', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedTinyInteger('status')->default(0);
        });
    }

    private function seedSceneFields(): void
    {
        DB::table('scene_fields')->insert([
            [
                'page' => 'CashierRetailIndex',
                'field' => 'status',
                'field_alias' => 'status',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '不等于兼容', 'value' => '!='],
                ], JSON_THROW_ON_ERROR),
            ],
            [
                'page' => 'CashierRetailIndex',
                'field' => 'created_at',
                'field_alias' => 'created_at',
                'operators' => json_encode([
                    ['text' => '在区间', 'value' => 'between'],
                ], JSON_THROW_ON_ERROR),
            ],
            [
                'page' => 'CashierRetailIndex',
                'field' => 'medium_id',
                'field_alias' => 'medium_id',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ], JSON_THROW_ON_ERROR),
            ],
        ]);
    }

    private function seedCashierRetailDependencies(): void
    {
        DB::table('customer')->insert([
            'id' => $this->customerId,
            'balance' => 1000,
        ]);

        DB::table('medium')->insert([
            'id' => 1,
        ]);

        DB::table('cashier_retail')->insert([
            'id' => $this->cashierRetailId,
            'status' => CashierRetailStatus::PENDING->value,
        ]);
    }

    private function dropFixtureTables(): void
    {
        Schema::dropIfExists('cashier_retail');
        Schema::dropIfExists('medium');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('scene_fields');
    }

    private function assertApplicationRouteExists(string $uri, string $method, string $action): void
    {
        $this->assertTrue(
            $this->hasRoute($uri, $method, $action),
            sprintf('Missing app route [%s %s] => %s from routes/web.php', $method, $uri, $action)
        );
    }

    private function hasRoute(string $uri, string $method, string $action): bool
    {
        foreach (Route::getRoutes() as $route) {
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
