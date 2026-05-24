<?php

namespace Tests\Feature\RetailOutbound;

use App\Http\Controllers\Web\RetailOutboundController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RetailOutboundManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('retail-outbound/manage', 'POST', RetailOutboundController::class.'@manage');
        Route::post('/retail-outbound/manage', [RetailOutboundController::class, 'manage']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('retail_outbound_detail');
        Schema::dropIfExists('retail_outbound');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('users');
        Schema::dropIfExists('department');
        Schema::dropIfExists('warehouse');

        parent::tearDown();
    }

    public function test_manage_keyword_hits_customer_keyword(): void
    {
        $this->seedSceneFields();
        $this->seedBaseRows();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'keyword' => '目标顾客',
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame(1, $payload['data']['rows'][0]['id']);
        $this->assertSame('客户甲', $payload['data']['rows'][0]['customer']['name']);
    }

    public function test_manage_applies_retail_outbound_scene_filters(): void
    {
        $this->seedSceneFields();
        $this->seedBaseRows();

        $payload = $this->dispatchManage([
            'rows' => 10,
            'page' => 1,
            'filters' => [
                [
                    'field' => 'warehouse_id',
                    'operator' => '=',
                    'value' => 2,
                ],
            ],
            'sort' => 'created_at',
            'order' => 'asc',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame(2, $payload['data']['rows'][0]['id']);
        $this->assertSame(2, $payload['data']['rows'][0]['warehouse_id']);
    }

    private function dispatchManage(array $data): array
    {
        $request = Request::create(
            '/retail-outbound/manage',
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
                'page' => 'RetailOutboundIndex',
                'name' => '出料仓库',
                'table' => 'retail_outbound',
                'field' => 'warehouse_id',
                'field_alias' => 'warehouse_id',
                'field_type' => 'int',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ], JSON_THROW_ON_ERROR),
                'query_config' => null,
                'keyword' => '出料仓库',
            ],
        ]);
    }

    private function seedBaseRows(): void
    {
        DB::table('warehouse')->insert([
            ['id' => 1, 'name' => '一号仓', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '二号仓', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('department')->insert([
            ['id' => 1, 'name' => '药房', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '皮肤科', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('users')->insert([
            ['id' => 11, 'name' => '出料员甲', 'email' => 'u11@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '录单员乙', 'email' => 'u12@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('customer')->insert([
            [
                'id' => 'customer-1',
                'name' => '客户甲',
                'idcard' => 'C001',
                'keyword' => '客户甲,目标顾客,13800138000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'customer-2',
                'name' => '客户乙',
                'idcard' => 'C002',
                'keyword' => '客户乙,其他顾客,13900139000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('retail_outbound')->insert([
            [
                'id' => 1,
                'key' => 'LSCL202605200001',
                'date' => '2026-05-20',
                'customer_id' => 'customer-1',
                'amount' => 20.0000,
                'department_id' => 1,
                'warehouse_id' => 1,
                'remark' => '目标单据',
                'user_id' => 11,
                'create_user_id' => 12,
                'created_at' => '2026-05-20 09:00:00',
                'updated_at' => '2026-05-20 09:00:00',
            ],
            [
                'id' => 2,
                'key' => 'LSCL202605200002',
                'date' => '2026-05-20',
                'customer_id' => 'customer-2',
                'amount' => 30.0000,
                'department_id' => 2,
                'warehouse_id' => 2,
                'remark' => '其他单据',
                'user_id' => 11,
                'create_user_id' => 12,
                'created_at' => '2026-05-20 10:00:00',
                'updated_at' => '2026-05-20 10:00:00',
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

        Schema::create('warehouse', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('retail_outbound', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->date('date');
            $table->uuid('customer_id');
            $table->decimal('amount', 14, 4)->default(0);
            $table->integer('department_id');
            $table->integer('warehouse_id');
            $table->text('remark')->nullable();
            $table->integer('user_id');
            $table->integer('create_user_id');
            $table->timestamps();
        });

        Schema::create('retail_outbound_detail', function (Blueprint $table): void {
            $table->id();
            $table->integer('retail_outbound_id');
            $table->string('key');
            $table->date('date');
            $table->integer('warehouse_id');
            $table->integer('department_id');
            $table->uuid('customer_id');
            $table->string('goods_name');
            $table->decimal('number', 14, 4)->default(0);
            $table->integer('unit_id')->nullable();
            $table->string('unit_name')->nullable();
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
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
