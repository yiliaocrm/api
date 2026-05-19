<?php

namespace Tests\Feature\Erkai;

use App\Enums\ErkaiStatus;
use App\Http\Requests\Erkai\ErkaiRequest;
use Database\Seeders\Tenant\SceneFields\ErkaiIndexSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ErkaiRequestTest extends TestCase
{
    private string $erkaiId;

    private string $customerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->erkaiId = (string) Str::uuid();
        $this->customerId = (string) Str::uuid();
        $this->assertApplicationRouteExists('erkai/manage', 'POST', 'App\Http\Controllers\Web\ErkaiController@manage');
        $this->assertApplicationRouteExists('erkai/info', 'GET', 'App\Http\Controllers\Web\ErkaiController@info');
        $this->assertApplicationRouteExists('erkai/create', 'POST', 'App\Http\Controllers\Web\ErkaiController@create');
        $this->assertApplicationRouteExists('erkai/update', 'POST', 'App\Http\Controllers\Web\ErkaiController@update');

        $this->dropFixtureTables();
        $this->createFixtureTables();
        $this->seedDependencies();
    }

    protected function tearDown(): void
    {
        $this->dropFixtureTables();
        parent::tearDown();
    }

    public function test_manage_requires_date_range(): void
    {
        $validator = $this->makeValidator('manage', 'POST', []);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }

    public function test_manage_rejects_filter_field_not_in_erkai_scene_config(): void
    {
        $validator = $this->makeValidator('manage', 'POST', [
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'not_exists_field',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('filters', $validator->errors()->toArray());
    }

    public function test_manage_accepts_configured_scene_filter_field(): void
    {
        $validator = $this->makeValidator('manage', 'POST', [
            'rows' => 20,
            'page' => 1,
            'keyword' => '关键字',
            'date' => ['2026-03-01', '2026-03-31'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_info_requires_existing_erkai_id(): void
    {
        $validator = $this->makeValidator('info', 'GET', [
            'id' => (string) Str::uuid(),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('id', $validator->errors()->toArray());
    }

    public function test_update_restricts_id_to_status_three_records(): void
    {
        $validator = $this->makeValidator('update', 'POST', $this->makeCreateOrUpdatePayload([
            'id' => $this->erkaiId,
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('id', $validator->errors()->toArray());
    }

    public function test_create_rejects_duplicate_goods_rows(): void
    {
        $payload = $this->makeCreateOrUpdatePayload([
            'detail' => [
                $this->makeGoodsDetailRow(1, '测试商品A'),
                $this->makeGoodsDetailRow(1, '测试商品A'),
            ],
        ]);

        $validator = $this->makeValidator('create', 'POST', $payload);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('detail.0.goods_id', $validator->errors()->toArray());
    }

    public function test_erkai_index_scene_field_seeder_contains_required_fields(): void
    {
        $config = (new ErkaiIndexSeeder)->getConfig();
        $fieldMap = collect($config)
            ->where('page', 'ErkaiIndex')
            ->mapWithKeys(fn ($item) => [$item['field'] => $item['name']])
            ->all();

        $this->assertEqualsCanonicalizing([
            'status',
            'department_id',
            'payable',
            'income',
            'deposit',
            'coupon',
            'arrearage',
            'user_id',
            'remark',
        ], array_keys($fieldMap));

        $this->assertSame([
            'status' => '状态',
            'department_id' => '二开科室',
            'payable' => '应收金额',
            'income' => '实收金额',
            'deposit' => '余额支付',
            'coupon' => '券支付',
            'arrearage' => '欠费金额',
            'user_id' => '录单人员',
            'remark' => '备注信息',
        ], $fieldMap);
    }

    public function test_erkai_index_status_field_uses_enum_options_for_select_component(): void
    {
        $config = (new ErkaiIndexSeeder)->getConfig();
        $statusField = collect($config)->firstWhere('field', 'status');
        $componentParams = json_decode($statusField['component_params'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $options = collect($componentParams['options'] ?? [])
            ->mapWithKeys(fn (array $item) => [$item['value'] => $item['label']])
            ->all();

        $this->assertSame(ErkaiStatus::options(), $options);
    }

    public function test_erkai_index_scene_filter_select_fields_support_multi_select_operators(): void
    {
        $config = collect((new ErkaiIndexSeeder)->getConfig());

        $statusField = $config->firstWhere('field', 'status');
        $statusOperators = collect(json_decode($statusField['operators'] ?? '[]', true, 512, JSON_THROW_ON_ERROR))
            ->pluck('value')
            ->all();
        $statusParams = json_decode($statusField['component_params'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);

        $departmentField = $config->firstWhere('field', 'department_id');
        $departmentOperators = collect(json_decode($departmentField['operators'] ?? '[]', true, 512, JSON_THROW_ON_ERROR))
            ->pluck('value')
            ->all();
        $departmentParams = json_decode($departmentField['component_params'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['=', '<>'], $statusOperators);
        $this->assertTrue($statusParams['props']['clearable'] ?? false);
        $this->assertTrue($statusParams['props']['filterable'] ?? false);
        $this->assertTrue($statusParams['props']['multiple'] ?? false);

        $this->assertSame(['=', '<>'], $departmentOperators);
        $this->assertTrue($departmentParams['props']['clearable'] ?? false);
        $this->assertTrue($departmentParams['props']['filterable'] ?? false);
        $this->assertTrue($departmentParams['props']['multiple'] ?? false);
    }

    public function test_erkai_index_remark_field_supports_text_search(): void
    {
        $config = collect((new ErkaiIndexSeeder)->getConfig());
        $remarkField = $config->firstWhere('field', 'remark');
        $operators = collect(json_decode($remarkField['operators'] ?? '[]', true, 512, JSON_THROW_ON_ERROR))
            ->pluck('value')
            ->all();

        $this->assertNotNull($remarkField);
        $this->assertSame('备注信息', $remarkField['name']);
        $this->assertSame('input', $remarkField['component']);
        $this->assertContains('like', $operators);
    }

    private function makeValidator(string $action, string $method, array $payload)
    {
        $uri = sprintf('/erkai/%s', $action);
        $request = ErkaiRequest::create($uri, $method, $payload);
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

    private function makeCreateOrUpdatePayload(array $overrides = []): array
    {
        $payload = [
            'customer_id' => $this->customerId,
            'form' => [
                'department_id' => 10,
                'type' => 1,
                'medium_id' => 1,
                'remark' => '测试备注',
            ],
            'detail' => [
                $this->makeGoodsDetailRow(1, '测试商品A'),
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }

    private function makeGoodsDetailRow(int $goodsId, string $goodsName): array
    {
        return [
            'type' => 'goods',
            'goods_id' => $goodsId,
            'goods_name' => $goodsName,
            'times' => 1,
            'unit_id' => 11,
            'price' => 100,
            'sales_price' => 100,
            'payable' => 100,
            'department_id' => 10,
            'salesman' => [1],
            'remark' => null,
        ];
    }

    private function createFixtureTables(): void
    {
        Schema::create('scene_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('page')->nullable();
            $table->string('field')->nullable();
            $table->string('field_alias')->nullable();
            $table->text('operators')->nullable();
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->integer('id')->primary();
        });

        Schema::create('medium', function (Blueprint $table): void {
            $table->integer('id')->primary();
        });

        Schema::create('product', function (Blueprint $table): void {
            $table->integer('id')->primary();
        });

        Schema::create('product_package', function (Blueprint $table): void {
            $table->integer('id')->primary();
        });

        Schema::create('goods', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->decimal('inventory_number', 14, 4)->default(0);
        });

        Schema::create('goods_unit', function (Blueprint $table): void {
            $table->id();
            $table->integer('goods_id');
            $table->integer('unit_id');
            $table->decimal('rate', 14, 4)->default(1);
        });

        Schema::create('erkai', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->tinyInteger('status')->default(1);
        });
    }

    private function seedDependencies(): void
    {
        DB::table('scene_fields')->insert([
            [
                'page' => 'ErkaiIndex',
                'field' => 'status',
                'field_alias' => 'status',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                ], JSON_THROW_ON_ERROR),
            ],
            [
                'page' => 'ErkaiIndex',
                'field' => 'department_id',
                'field_alias' => 'department_id',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                ], JSON_THROW_ON_ERROR),
            ],
        ]);

        DB::table('customer')->insert([
            ['id' => $this->customerId],
        ]);

        DB::table('department')->insert([
            ['id' => 10],
        ]);

        DB::table('medium')->insert([
            ['id' => 1],
        ]);

        DB::table('product')->insert([
            ['id' => 1],
        ]);

        DB::table('product_package')->insert([
            ['id' => 1],
        ]);

        DB::table('goods')->insert([
            ['id' => 1, 'inventory_number' => 100],
        ]);

        DB::table('goods_unit')->insert([
            [
                'goods_id' => 1,
                'unit_id' => 11,
                'rate' => 1,
            ],
        ]);

        DB::table('erkai')->insert([
            [
                'id' => $this->erkaiId,
                'status' => 1,
            ],
        ]);
    }

    private function dropFixtureTables(): void
    {
        Schema::dropIfExists('erkai');
        Schema::dropIfExists('goods_unit');
        Schema::dropIfExists('goods');
        Schema::dropIfExists('product_package');
        Schema::dropIfExists('product');
        Schema::dropIfExists('medium');
        Schema::dropIfExists('department');
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
