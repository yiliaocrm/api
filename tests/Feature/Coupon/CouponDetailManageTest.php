<?php

namespace Tests\Feature\Coupon;

use App\Enums\CouponDetailStatus;
use App\Exports\CouponDetailExport;
use App\Http\Controllers\Web\CouponController;
use App\Http\Controllers\Web\CouponDetailController;
use App\Http\Controllers\Web\ExportController;
use App\Http\Controllers\Web\ExportController as WebExportController;
use App\Models\ExportTask;
use App\Models\User;
use Database\Seeders\Tenant\PermissionActionSeeder;
use Database\Seeders\Tenant\SceneFields\CouponDetailIndexSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Tenancy;
use Tests\TestCase;

class CouponDetailManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        Route::get('/coupon-detail/manage', [CouponDetailController::class, 'manage']);
        Route::post('/coupon-detail/void', [CouponDetailController::class, 'void']);
        Route::get('/coupon/detail', [CouponController::class, 'detail']);
        Route::post('/export/coupon/detail', [ExportController::class, 'couponDetail']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('coupon_detail_histories');
        Schema::dropIfExists('coupon_details');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('export_tasks');
        Schema::dropIfExists('permission_actions');
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('users');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    public function test_manage_filters_by_header_date_keyword_and_scene_filters(): void
    {
        $this->seedSceneFields();
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedCoupons();
        $this->seedCouponDetails();

        $payload = $this->dispatchRequest('/coupon-detail/manage', 'GET', [
            'rows' => 10,
            'page' => 1,
            'created_at_start' => '2026-05-01',
            'created_at_end' => '2026-05-31',
            'keyword' => '张三',
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
        $this->assertSame('目标卡券', $payload['data']['rows'][0]['coupon_name']);
        $this->assertSame('未使用', $payload['data']['rows'][0]['status_text']);
    }

    public function test_coupon_detail_scene_field_seeder_excludes_header_fields(): void
    {
        $config = (new CouponDetailIndexSeeder)->getConfig();
        $fields = array_column($config, 'field');

        $this->assertNotContains('name', $fields);
        $this->assertNotContains('idcard', $fields);
        $this->assertNotContains('created_at', $fields);
        $this->assertContains('status', $fields);
        $this->assertContains('coupon_name', $fields);
        $this->assertContains('number', $fields);
        $this->assertContains('create_user_id', $fields);

        $statusField = collect($config)->firstWhere('field', 'status');
        $componentParams = json_decode($statusField['component_params'], true, 512, JSON_THROW_ON_ERROR);
        $expectedOptions = collect(CouponDetailStatus::options())
            ->map(fn (string $text, int $value): array => [
                'label' => $text,
                'value' => $value,
            ])
            ->values()
            ->all();

        $this->assertSame($expectedOptions, $componentParams['options']);
    }

    public function test_coupon_detail_records_expose_status_text(): void
    {
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedCoupons();
        $this->seedCouponDetails();

        $payload = $this->dispatchRequest('/coupon/detail', 'GET', [
            'rows' => 10,
            'page' => 1,
            'coupon_id' => 1,
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(5, $payload['data']['total']);
        $row = collect($payload['data']['rows'])->firstWhere('id', 1);
        $this->assertSame('未使用', $row['status_text']);
    }

    public function test_void_marks_available_coupon_detail_invalid_and_writes_history(): void
    {
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedCouponDetails();

        $payload = $this->dispatchRequest('/coupon-detail/void', 'POST', [
            'id' => 1,
            'remark' => '前台作废',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertDatabaseHas('coupon_details', [
            'id' => 1,
            'status' => 5,
            'balance' => 0,
        ]);
        $this->assertDatabaseHas('coupon_detail_histories', [
            'coupon_detail_id' => 1,
            'coupon_number' => 'CP001',
            'before' => 100,
            'amount' => -100,
            'after' => 0,
            'remark' => '前台作废',
        ]);

        $managePayload = $this->dispatchRequest('/coupon-detail/manage', 'GET', [
            'rows' => 10,
            'page' => 1,
            'status' => 5,
        ]);

        $this->assertSame(200, $managePayload['code']);
        $this->assertSame('已作废', $managePayload['data']['rows'][0]['status_text']);
    }

    public function test_void_rejects_used_expired_or_invalid_coupon_detail(): void
    {
        $this->seedUsers();
        $this->seedCustomers();
        $this->seedCouponDetails();

        foreach ([3 => 3, 4 => 4, 5 => 5] as $id => $status) {
            $payload = $this->dispatchRequest('/coupon-detail/void', 'POST', [
                'id' => $id,
                'remark' => '重复作废',
            ]);

            $this->assertNotSame(200, $payload['code']);
            $this->assertDatabaseHas('coupon_details', [
                'id' => $id,
                'status' => $status,
                'balance' => 0,
            ]);
        }
    }

    public function test_coupon_detail_export_creates_task_and_dispatches_async_job(): void
    {
        Bus::fake();
        $this->bindTenant('whms');
        $this->seedSceneFields();
        $this->seedUsers();
        $user = new class extends User implements Authenticatable
        {
            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): int
            {
                return 1;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): string
            {
                return '';
            }

            public function getRememberToken(): ?string
            {
                return null;
            }

            public function setRememberToken($value): void {}

            public function getRememberTokenName(): string
            {
                return 'remember_token';
            }
        };
        $user->id = 1;
        auth()->setUser($user);

        $payload = $this->dispatchRequest('/export/coupon/detail', 'POST', [
            'created_at_start' => '2026-05-01',
            'created_at_end' => '2026-05-31',
            'keyword' => '张三',
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
            'fileName' => '领券记录',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertDatabaseHas('export_tasks', [
            'name' => '领券记录',
            'status' => 'pending',
            'user_id' => 1,
        ]);
        $task = ExportTask::query()->firstOrFail();
        $this->assertSame([
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 1,
                ],
            ],
            'keyword' => '张三',
            'created_at_start' => '2026-05-01',
            'created_at_end' => '2026-05-31',
        ], $task->params);

        Bus::assertDispatched(CouponDetailExport::class);
    }

    public function test_coupon_detail_permission_actions_include_index_and_export(): void
    {
        (new PermissionActionSeeder)->run();

        $this->assertDatabaseHas('permission_actions', [
            'permission' => 'coupon.detail.index',
            'controller' => CouponDetailController::class,
            'action' => 'manage,histories',
        ]);
        $this->assertDatabaseHas('permission_actions', [
            'permission' => 'coupon.detail.export',
            'controller' => WebExportController::class,
            'action' => 'couponDetail',
        ]);
    }

    private function dispatchRequest(string $uri, string $method, array $data): array
    {
        $request = Request::create(
            $uri,
            $method,
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
                $field['api'] = $field['api'] ?? null;
                $field['field_alias'] = $field['field_alias'] ?? null;
                $field['query_config'] = $field['query_config'] ?? null;
                $field['component_params'] = $field['component_params'] ?? null;
                $field['keyword'] = implode(',', parse_pinyin($field['name']));

                return $field;
            },
            (new CouponDetailIndexSeeder)->getConfig()
        );

        DB::table('scene_fields')->insert($sceneFields);
    }

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => '发券人',
            ],
        ]);
    }

    private function seedCustomers(): void
    {
        DB::table('customer')->insert([
            [
                'id' => '00000000-0000-0000-0000-000000000001',
                'name' => '张三',
                'idcard' => 'C001',
                'mobile' => '13800000001',
                'keyword' => '张三,C001,13800000001',
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000002',
                'name' => '李四',
                'idcard' => 'C002',
                'mobile' => '13800000002',
                'keyword' => '李四,C002,13800000002',
            ],
        ]);
    }

    private function seedCoupons(): void
    {
        DB::table('coupons')->insert([
            [
                'id' => 1,
                'name' => '测试卡券',
            ],
        ]);
    }

    private function seedCouponDetails(): void
    {
        DB::table('coupon_details')->insert([
            [
                'id' => 1,
                'status' => 1,
                'coupon_id' => 1,
                'coupon_name' => '目标卡券',
                'coupon_value' => 100,
                'balance' => 100,
                'customer_id' => '00000000-0000-0000-0000-000000000001',
                'number' => 'CP001',
                'sales_price' => 0,
                'integrals' => 0,
                'expire_time' => '2026-12-31 23:59:59',
                'rate' => 0,
                'department_id' => 1,
                'salesman' => null,
                'remark' => null,
                'create_user_id' => 1,
                'created_at' => '2026-05-10 10:00:00',
                'updated_at' => '2026-05-10 10:00:00',
            ],
            [
                'id' => 2,
                'status' => 1,
                'coupon_id' => 1,
                'coupon_name' => '其他卡券',
                'coupon_value' => 100,
                'balance' => 100,
                'customer_id' => '00000000-0000-0000-0000-000000000002',
                'number' => 'CP002',
                'sales_price' => 0,
                'integrals' => 0,
                'expire_time' => '2026-12-31 23:59:59',
                'rate' => 0,
                'department_id' => 1,
                'salesman' => null,
                'remark' => null,
                'create_user_id' => 1,
                'created_at' => '2026-05-10 10:00:00',
                'updated_at' => '2026-05-10 10:00:00',
            ],
            [
                'id' => 3,
                'status' => 3,
                'coupon_id' => 1,
                'coupon_name' => '已用卡券',
                'coupon_value' => 100,
                'balance' => 0,
                'customer_id' => '00000000-0000-0000-0000-000000000001',
                'number' => 'CP003',
                'sales_price' => 0,
                'integrals' => 0,
                'expire_time' => '2026-12-31 23:59:59',
                'rate' => 0,
                'department_id' => 1,
                'salesman' => null,
                'remark' => null,
                'create_user_id' => 1,
                'created_at' => '2026-05-10 10:00:00',
                'updated_at' => '2026-05-10 10:00:00',
            ],
            [
                'id' => 4,
                'status' => 4,
                'coupon_id' => 1,
                'coupon_name' => '已过期卡券',
                'coupon_value' => 100,
                'balance' => 0,
                'customer_id' => '00000000-0000-0000-0000-000000000001',
                'number' => 'CP004',
                'sales_price' => 0,
                'integrals' => 0,
                'expire_time' => '2026-01-01 23:59:59',
                'rate' => 0,
                'department_id' => 1,
                'salesman' => null,
                'remark' => null,
                'create_user_id' => 1,
                'created_at' => '2026-05-10 10:00:00',
                'updated_at' => '2026-05-10 10:00:00',
            ],
            [
                'id' => 5,
                'status' => 5,
                'coupon_id' => 1,
                'coupon_name' => '已作废卡券',
                'coupon_value' => 100,
                'balance' => 0,
                'customer_id' => '00000000-0000-0000-0000-000000000001',
                'number' => 'CP005',
                'sales_price' => 0,
                'integrals' => 0,
                'expire_time' => '2026-12-31 23:59:59',
                'rate' => 0,
                'department_id' => 1,
                'salesman' => null,
                'remark' => null,
                'create_user_id' => 1,
                'created_at' => '2026-05-10 10:00:00',
                'updated_at' => '2026-05-10 10:00:00',
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
            $table->id();
            $table->string('name');
        });

        Schema::create('permission_actions', function (Blueprint $table): void {
            $table->id();
            $table->string('permission');
            $table->string('controller');
            $table->text('action')->nullable();
            $table->text('except')->nullable();
            $table->timestamps();
        });

        Schema::create('export_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('hash')->nullable();
            $table->string('status')->default('pending');
            $table->json('params')->nullable();
            $table->string('file_path')->nullable();
            $table->longText('error_message')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->string('mobile')->nullable();
            $table->text('keyword')->nullable();
        });

        Schema::create('coupon_details', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('status');
            $table->integer('coupon_id');
            $table->string('coupon_name');
            $table->decimal('coupon_value', 14, 4);
            $table->decimal('balance', 14, 4);
            $table->uuid('customer_id')->nullable();
            $table->string('number')->unique();
            $table->decimal('sales_price', 14, 4);
            $table->decimal('integrals', 14, 4);
            $table->dateTime('expire_time');
            $table->decimal('rate', 14, 4);
            $table->integer('department_id');
            $table->text('salesman')->nullable();
            $table->text('remark')->nullable();
            $table->integer('create_user_id');
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('coupon_detail_histories', function (Blueprint $table): void {
            $table->id();
            $table->integer('coupon_id');
            $table->integer('coupon_detail_id');
            $table->string('coupon_number');
            $table->uuid('customer_id')->nullable();
            $table->decimal('before', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->decimal('after', 14, 4)->default(0);
            $table->string('remark')->nullable();
            $table->nullableUuidMorphs('historyable');
            $table->timestamps();
        });
    }

    private function bindTenant(string $id): void
    {
        $tenant = new class($id) implements Tenant
        {
            public function __construct(private readonly string $id) {}

            public function getTenantKeyName(): string
            {
                return 'id';
            }

            public function getTenantKey()
            {
                return $this->id;
            }

            public function getInternal(string $key)
            {
                return $this->getAttribute($key);
            }

            public function setInternal(string $key, $value)
            {
                return $this;
            }

            public function getAttribute($key)
            {
                return $key === 'id' ? $this->id : null;
            }

            public function setAttribute($key, $value)
            {
                return $this;
            }

            public function put($key, $value = null)
            {
                return $this;
            }

            public function get($key, $default = null)
            {
                return $key === 'id' ? $this->id : $default;
            }

            public function has($key): bool
            {
                return $key === 'id';
            }

            public function run(callable $callback)
            {
                return $callback($this);
            }
        };

        $tenancy = new Tenancy;
        $tenancy->tenant = $tenant;
        $tenancy->initialized = true;

        app()->instance(Tenant::class, $tenant);
        app()->instance(Tenancy::class, $tenancy);
    }
}
