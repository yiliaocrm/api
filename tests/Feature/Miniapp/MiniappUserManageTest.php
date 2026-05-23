<?php

namespace Tests\Feature\Miniapp;

use App\Http\Controllers\Web\MiniappController;
use Database\Seeders\Tenant\PermissionActionSeeder;
use Database\Seeders\Tenant\SceneFields\MiniappUserIndexSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MiniappUserManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        Route::post('/miniapp/user/index', [MiniappController::class, 'getUserList']);
        Route::post('/miniapp/user/change', [MiniappController::class, 'change']);
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_log');
        Schema::dropIfExists('permission_actions');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('customer_wechats');
        Schema::dropIfExists('scene_fields');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    public function test_user_index_filters_by_customer_keyword_register_date_and_scene_filters(): void
    {
        $this->seedSceneFields();
        $this->seedCustomers();
        $this->seedCustomerWechats();

        $payload = $this->dispatchRequest('/miniapp/user/index', 'POST', [
            'rows' => 10,
            'page' => 1,
            'created_at' => ['2026-01-01', '2026-12-31'],
            'keyword' => '张三',
            'filters' => [
                [
                    'field' => 'nickname',
                    'operator' => 'like',
                    'value' => '目标',
                ],
                [
                    'field' => 'phone',
                    'operator' => 'like',
                    'value' => '0001',
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame('张三', $payload['data']['rows'][0]['customer_name']);
        $this->assertSame('C001', $payload['data']['rows'][0]['customer_idcard']);
        $this->assertSame('目标昵称', $payload['data']['rows'][0]['nickname']);
    }

    public function test_change_updates_customer_binding_and_removes_old_customer_tokens(): void
    {
        $this->seedCustomers();
        $this->seedCustomerWechats();
        DB::table('personal_access_tokens')->insert([
            [
                'id' => 1,
                'tokenable_type' => 'App\\Models\\Customer',
                'tokenable_id' => '00000000-0000-0000-0000-000000000001',
                'name' => 'wechat',
                'token' => 'old-token',
                'abilities' => json_encode(['*']),
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ],
            [
                'id' => 2,
                'tokenable_type' => 'App\\Models\\Customer',
                'tokenable_id' => '00000000-0000-0000-0000-000000000002',
                'name' => 'wechat',
                'token' => 'new-token',
                'abilities' => json_encode(['*']),
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ],
        ]);

        $payload = $this->dispatchRequest('/miniapp/user/change', 'POST', [
            'id' => 1,
            'customer_id' => '00000000-0000-0000-0000-000000000002',
        ]);

        $this->assertSame(200, $payload['code']);
        $this->assertDatabaseHas('customer_wechats', [
            'id' => 1,
            'customer_id' => '00000000-0000-0000-0000-000000000002',
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => 1,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => 2,
        ]);
        $this->assertDatabaseHas('customer_log', [
            'customer_id' => '00000000-0000-0000-0000-000000000002',
            'logable_type' => 'App\\Models\\CustomerWechat',
            'logable_id' => 1,
        ]);
    }

    public function test_scene_field_seeder_excludes_header_columns(): void
    {
        $config = (new MiniappUserIndexSeeder)->getConfig();
        $fields = array_column($config, 'field');

        $this->assertNotContains('name', $fields);
        $this->assertNotContains('idcard', $fields);
        $this->assertNotContains('created_at', $fields);
        $this->assertContains('nickname', $fields);
        $this->assertContains('phone', $fields);
    }

    public function test_permission_action_allows_all_miniapp_user_controller_actions(): void
    {
        (new PermissionActionSeeder)->run();

        $this->assertDatabaseHas('permission_actions', [
            'permission' => 'miniapp.user.index',
            'controller' => MiniappController::class,
            'action' => '*',
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
            (new MiniappUserIndexSeeder)->getConfig()
        );

        DB::table('scene_fields')->insert($sceneFields);
    }

    private function seedCustomers(): void
    {
        DB::table('customer')->insert([
            [
                'id' => '00000000-0000-0000-0000-000000000001',
                'name' => '张三',
                'idcard' => 'C001',
                'keyword' => '张三,C001,13800000001',
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000002',
                'name' => '李四',
                'idcard' => 'C002',
                'keyword' => '李四,C002,13800000002',
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000003',
                'name' => '王五',
                'idcard' => 'C003',
                'keyword' => '王五,C003,13800000003',
            ],
        ]);
    }

    private function seedCustomerWechats(): void
    {
        DB::table('customer_wechats')->insert([
            [
                'id' => 1,
                'customer_id' => '00000000-0000-0000-0000-000000000001',
                'nickname' => '目标昵称',
                'phone' => '13800000001',
                'open_id' => 'openid-1',
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ],
            [
                'id' => 2,
                'customer_id' => '00000000-0000-0000-0000-000000000002',
                'nickname' => '目标昵称',
                'phone' => '13800000002',
                'open_id' => 'openid-2',
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ],
            [
                'id' => 3,
                'customer_id' => '00000000-0000-0000-0000-000000000003',
                'nickname' => '过期昵称',
                'phone' => '13800000001',
                'open_id' => 'openid-3',
                'created_at' => '2025-05-01 10:00:00',
                'updated_at' => '2025-05-01 10:00:00',
            ],
        ]);
    }

    private function createTables(): void
    {
        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->text('keyword')->nullable();
        });

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

        Schema::create('customer_wechats', function (Blueprint $table): void {
            $table->id();
            $table->uuid('customer_id')->nullable();
            $table->string('nickname')->nullable();
            $table->string('phone')->nullable();
            $table->string('open_id')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_log', function (Blueprint $table): void {
            $table->id();
            $table->uuid('customer_id');
            $table->nullableMorphs('logable');
            $table->string('action')->nullable();
            $table->unsignedBigInteger('user_id')->default(0);
            $table->timestamps();
        });

        Schema::create('permission_actions', function (Blueprint $table): void {
            $table->id();
            $table->string('permission');
            $table->string('controller');
            $table->text('action')->nullable();
            $table->text('except')->nullable();
            $table->timestamps();
        });
    }
}
