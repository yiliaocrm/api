<?php

namespace Tests\Feature\Web;

use App\Http\Controllers\Web\WorkbenchController;
use App\Models\User;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkbenchConsultantTest extends TestCase
{
    private string $originalTablePrefix = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        Route::post('/workbench/consultant', [WorkbenchController::class, 'consultant']);

        $this->originalTablePrefix = DB::connection()->getTablePrefix();
        DB::connection()->setTablePrefix('cy_');
        Carbon::setTestNow('2026-05-11 09:00:00');
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('reception_items');
        Schema::dropIfExists('reception');
        Schema::dropIfExists('reception_type');
        Schema::dropIfExists('failure');
        Schema::dropIfExists('item');
        Schema::dropIfExists('department');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('users');
        DB::connection()->setTablePrefix($this->originalTablePrefix);
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_consultant_filters_by_date_keyword_and_scene_filters(): void
    {
        $this->actingAsWorkbenchUser(['superuser' => true]);
        $this->seedSceneFields();
        $this->seedReferenceData();
        $this->seedReceptionRows();

        $payload = $this->dispatchConsultant([
            'rows' => 10,
            'page' => 1,
            'keyword' => '目标',
            'created_at' => ['2026-05-01', '2026-05-31'],
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
        $this->assertSame('reception-1', $payload['data']['rows'][0]['id']);
        $this->assertSame('客户A', $payload['data']['rows'][0]['customer']['name']);
        $this->assertSame('未成交原因A', $payload['data']['rows'][0]['failure']['name']);
        $this->assertSame('咨询科室A', $payload['data']['rows'][0]['department']['name']);
        $this->assertSame('咨询师A', $payload['data']['rows'][0]['consultant_user']['name']);
        $this->assertSame('成交', $payload['data']['rows'][0]['status_text']);
        $this->assertSame('咨询项目A', $payload['data']['rows'][0]['reception_items'][0]['name']);
    }

    public function test_consultant_rejects_unconfigured_scene_filter(): void
    {
        $this->actingAsWorkbenchUser(['superuser' => true]);
        $this->seedReferenceData();
        $this->seedReceptionRows();

        $payload = $this->dispatchConsultant([
            'rows' => 10,
            'page' => 1,
            'created_at' => ['2026-05-01', '2026-05-31'],
            'filters' => [
                [
                    'field' => 'unknown_field',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
        ]);

        $this->assertSame(400, $payload['code']);
    }

    public function test_consultant_limits_rows_by_consultant_view_permission(): void
    {
        $this->actingAsWorkbenchUser([
            'workbench.consultant.history' => true,
            'consultant.view.user.id.20' => true,
        ]);
        $this->seedSceneFields();
        $this->seedReferenceData();
        $this->seedReceptionRows();

        $payload = $this->dispatchConsultant([
            'rows' => 10,
            'page' => 1,
            'created_at' => ['2026-05-01', '2026-05-31'],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['total']);
        $this->assertSame(['reception-2', 'reception-1'], array_column($payload['data']['rows'], 'id'));
    }

    public function test_consultant_limits_rows_by_consultant_department_permission(): void
    {
        $this->actingAsWorkbenchUser([
            'workbench.consultant.history' => true,
            'consultant.view.department.id.2' => true,
        ]);
        $this->seedSceneFields();
        $this->seedReferenceData();
        $this->seedReceptionRows();

        $payload = $this->dispatchConsultant([
            'rows' => 10,
            'page' => 1,
            'created_at' => ['2026-05-01', '2026-05-31'],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['total']);
        $this->assertSame(['reception-3', 'reception-2'], array_column($payload['data']['rows'], 'id'));
    }

    public function test_consultant_without_history_permission_only_returns_today(): void
    {
        $this->actingAsWorkbenchUser([
            'consultant.view.all' => true,
        ]);
        $this->seedSceneFields();
        $this->seedReferenceData();
        $this->seedReceptionRows();

        $payload = $this->dispatchConsultant([
            'rows' => 10,
            'page' => 1,
            'created_at' => ['2026-05-01', '2026-05-31'],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('reception-2', $payload['data']['rows'][0]['id']);
    }

    public function test_consultant_with_history_permission_can_query_history_range(): void
    {
        $this->actingAsWorkbenchUser([
            'consultant.view.all' => true,
            'workbench.consultant.history' => true,
        ]);
        $this->seedSceneFields();
        $this->seedReferenceData();
        $this->seedReceptionRows();

        $payload = $this->dispatchConsultant([
            'rows' => 10,
            'page' => 1,
            'created_at' => ['2026-05-01', '2026-05-31'],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(3, $payload['data']['total']);
        $this->assertSame(['reception-3', 'reception-2', 'reception-1'], array_column($payload['data']['rows'], 'id'));
    }

    private function dispatchConsultant(array $data): array
    {
        $request = Request::create(
            '/workbench/consultant',
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

    private function actingAsWorkbenchUser(array $permissions): void
    {
        DB::table('users')->insert([
            'id' => 10,
            'name' => '当前用户',
            'email' => 'current@example.com',
            'password' => 'secret',
            'department_id' => 1,
            'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
        ]);

        auth()->setUser(new class($permissions) extends User implements \Illuminate\Contracts\Auth\Authenticatable
        {
            use Authenticatable;

            public function __construct(private readonly array $testPermissions)
            {
                $this->id = 10;
                $this->department_id = 1;
            }

            public function hasAnyAccess($permissions): bool
            {
                foreach ((array) $permissions as $permission) {
                    if (($this->testPermissions[$permission] ?? false) === true) {
                        return true;
                    }
                }

                return false;
            }

            public function getMergedPermissions(): array
            {
                return $this->testPermissions;
            }
        });
    }

    private function seedSceneFields(): void
    {
        DB::table('scene_fields')->insert([
            [
                'page' => 'WorkbenchConsultant',
                'name' => '成交状态',
                'table' => 'reception',
                'field' => 'status',
                'field_alias' => 'status',
                'field_type' => 'integer',
                'operators' => json_encode([
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                ], JSON_THROW_ON_ERROR),
                'query_config' => null,
                'keyword' => '成交状态',
            ],
        ]);
    }

    private function seedReferenceData(): void
    {
        DB::table('users')->insert([
            ['id' => 20, 'name' => '咨询师A', 'email' => 'consultant-a@example.com', 'password' => 'secret', 'permissions' => null, 'department_id' => 1],
            ['id' => 30, 'name' => '二开A', 'email' => 'ek-a@example.com', 'password' => 'secret', 'permissions' => null, 'department_id' => 1],
            ['id' => 40, 'name' => '医生A', 'email' => 'doctor-a@example.com', 'password' => 'secret', 'permissions' => null, 'department_id' => 1],
            ['id' => 50, 'name' => '接待A', 'email' => 'reception-a@example.com', 'password' => 'secret', 'permissions' => null, 'department_id' => 1],
            ['id' => 60, 'name' => '录单A', 'email' => 'creator-a@example.com', 'password' => 'secret', 'permissions' => null, 'department_id' => 1],
            ['id' => 70, 'name' => '咨询师B', 'email' => 'consultant-b@example.com', 'password' => 'secret', 'permissions' => null, 'department_id' => 2],
        ]);

        DB::table('customer')->insert([
            ['id' => 'customer-1', 'name' => '客户A', 'idcard' => 'ID-A', 'keyword' => '客户A,目标', 'created_at' => '2026-05-01 09:00:00', 'updated_at' => '2026-05-01 09:00:00'],
            ['id' => 'customer-2', 'name' => '客户B', 'idcard' => 'ID-B', 'keyword' => '客户B,目标', 'created_at' => '2026-05-01 09:00:00', 'updated_at' => '2026-05-01 09:00:00'],
            ['id' => 'customer-3', 'name' => '客户C', 'idcard' => 'ID-C', 'keyword' => '客户C,其他', 'created_at' => '2026-05-01 09:00:00', 'updated_at' => '2026-05-01 09:00:00'],
        ]);

        DB::table('department')->insert([
            ['id' => 100, 'name' => '咨询科室A'],
            ['id' => 200, 'name' => '咨询科室B'],
        ]);

        DB::table('reception_type')->insert([
            ['id' => 1, 'name' => '初诊'],
            ['id' => 2, 'name' => '复诊'],
        ]);

        DB::table('failure')->insert([
            ['id' => 1, 'name' => '未成交原因A'],
        ]);

        DB::table('item')->insert([
            ['id' => 1, 'name' => '咨询项目A', 'tree' => '1'],
            ['id' => 2, 'name' => '咨询项目B', 'tree' => '2'],
        ]);
    }

    private function seedReceptionRows(): void
    {
        DB::table('reception')->insert([
            [
                'id' => 'reception-1',
                'customer_id' => 'customer-1',
                'status' => 2,
                'type' => 1,
                'receptioned' => 1,
                'department_id' => 100,
                'failure_id' => 1,
                'consultant' => 20,
                'ek_user' => 30,
                'doctor' => 40,
                'reception' => 50,
                'user_id' => 60,
                'created_at' => '2026-05-10 10:00:00',
                'updated_at' => '2026-05-10 10:00:00',
            ],
            [
                'id' => 'reception-2',
                'customer_id' => 'customer-2',
                'status' => 1,
                'type' => 2,
                'receptioned' => 1,
                'department_id' => 200,
                'failure_id' => null,
                'consultant' => 70,
                'ek_user' => 20,
                'doctor' => 40,
                'reception' => 50,
                'user_id' => 60,
                'created_at' => '2026-05-11 10:00:00',
                'updated_at' => '2026-05-11 10:00:00',
            ],
            [
                'id' => 'reception-3',
                'customer_id' => 'customer-3',
                'status' => 2,
                'type' => 1,
                'receptioned' => 1,
                'department_id' => 100,
                'failure_id' => null,
                'consultant' => 70,
                'ek_user' => 30,
                'doctor' => 40,
                'reception' => 50,
                'user_id' => 60,
                'created_at' => '2026-05-12 10:00:00',
                'updated_at' => '2026-05-12 10:00:00',
            ],
            [
                'id' => 'reception-out',
                'customer_id' => 'customer-1',
                'status' => 2,
                'type' => 1,
                'receptioned' => 1,
                'department_id' => 100,
                'failure_id' => 1,
                'consultant' => 20,
                'ek_user' => 30,
                'doctor' => 40,
                'reception' => 50,
                'user_id' => 60,
                'created_at' => '2026-04-10 10:00:00',
                'updated_at' => '2026-04-10 10:00:00',
            ],
        ]);

        DB::table('reception_items')->insert([
            ['reception_id' => 'reception-1', 'item_id' => 1],
            ['reception_id' => 'reception-2', 'item_id' => 2],
        ]);
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->json('permissions')->nullable();
            $table->integer('department_id')->nullable();
        });

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

        Schema::create('customer', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('item', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->string('tree')->nullable();
        });

        Schema::create('failure', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('reception_type', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('reception', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('customer_id')->index();
            $table->integer('status')->default(1);
            $table->integer('type')->nullable();
            $table->boolean('receptioned')->default(false);
            $table->integer('department_id')->nullable();
            $table->integer('failure_id')->nullable();
            $table->integer('consultant')->nullable();
            $table->integer('ek_user')->nullable();
            $table->integer('doctor')->nullable();
            $table->integer('reception')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('reception_items', function (Blueprint $table): void {
            $table->string('reception_id');
            $table->integer('item_id');
        });
    }
}
