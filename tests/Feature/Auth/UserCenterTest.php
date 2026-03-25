<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Web\AuthController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class UserCenterTest extends TestCase
{
    private User $currentUser;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        Cache::forget('admin_parameters');
        $this->createTables();
        $this->seedBaseData();
        $this->mockAuthUser();
    }

    protected function tearDown(): void
    {
        Cache::forget('admin_parameters');

        Schema::dropIfExists('users_login');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('role_users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('admin_parameters');
        Schema::dropIfExists('parameters');
        Schema::dropIfExists('department');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_it_returns_the_current_user_center_payload(): void
    {
        $this->assertApplicationRouteExists('auth/user-center', 'GET', AuthController::class.'@userCenter');

        [$response, $data] = $this->dispatchJsonRequest('GET', '/auth/user-center');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertSame($this->currentUser->id, data_get($data, 'data.user.id'));
        $this->assertSame('current@example.com', data_get($data, 'data.user.email'));
        $this->assertSame('Current User', data_get($data, 'data.user.name'));
        $this->assertSame('avatars/current.png', data_get($data, 'data.user.avatar'));
        $this->assertSame('Current remark', data_get($data, 'data.user.remark'));
        $this->assertSame('8001', data_get($data, 'data.user.extension'));
        $this->assertSame('2026-03-24 09:10:11', data_get($data, 'data.user.last_login'));
        $this->assertSame('2026-03-01 08:00:00', data_get($data, 'data.user.created_at'));
        $this->assertFalse((bool) data_get($data, 'data.user.banned'));
        $this->assertSame(1, data_get($data, 'data.user.department.id'));
        $this->assertSame('运营部', data_get($data, 'data.user.department.name'));
        $this->assertSame(1, data_get($data, 'data.user.roles.0.id'));
        $this->assertSame('consultant', data_get($data, 'data.user.roles.0.slug'));
        $this->assertSame('咨询师', data_get($data, 'data.user.roles.0.name'));
        $this->assertTrue((bool) data_get($data, 'data.user.has_secret'));
        $this->assertFalse((bool) data_get($data, 'data.security.force_totp'));

        $userPayload = data_get($data, 'data.user');

        $this->assertIsArray($userPayload);
        $this->assertArrayNotHasKey('password', $userPayload);
        $this->assertArrayNotHasKey('secret', $userPayload);
        $this->assertNotSame($this->otherUser->id, data_get($data, 'data.user.id'));
    }

    public function test_it_updates_only_the_allowed_profile_fields(): void
    {
        $this->assertApplicationRouteExists('auth/update-profile', 'POST', AuthController::class.'@updateProfile');

        [$response, $data] = $this->dispatchJsonRequest('POST', '/auth/update-profile', [
            'avatar' => 'avatars/updated.png',
            'name' => 'Updated User',
            'extension' => '8018',
            'remark' => 'Updated remark',
            'email' => 'hacker@example.com',
            'department_id' => 2,
            'roles' => [2],
            'banned' => 1,
            'password' => 'bad-password',
            'secret' => 'SHOULD_NOT_CHANGE',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertSame('avatars/updated.png', data_get($data, 'data.avatar'));
        $this->assertSame('Updated User', data_get($data, 'data.name'));
        $this->assertSame('8018', data_get($data, 'data.extension'));
        $this->assertSame('Updated remark', data_get($data, 'data.remark'));

        $freshUser = User::query()->findOrFail($this->currentUser->id);

        $this->assertSame('avatars/updated.png', $freshUser->avatar);
        $this->assertSame('Updated User', $freshUser->name);
        $this->assertSame('8018', $freshUser->extension);
        $this->assertSame('Updated remark', $freshUser->remark);
        $this->assertSame('current@example.com', $freshUser->email);
        $this->assertSame(1, $freshUser->department_id);
        $this->assertFalse((bool) $freshUser->banned);
        $this->assertSame('JBSWY3DPEHPK3PXP', $freshUser->secret);
        $this->assertTrue(Hash::check('old-password', $freshUser->password));
        $this->assertDatabaseHas('role_users', [
            'user_id' => $this->currentUser->id,
            'role_id' => 1,
        ]);
        $this->assertDatabaseMissing('role_users', [
            'user_id' => $this->currentUser->id,
            'role_id' => 2,
        ]);
    }

    public function test_it_rejects_duplicate_extension_when_updating_profile(): void
    {
        $this->assertApplicationRouteExists('auth/update-profile', 'POST', AuthController::class.'@updateProfile');

        [$response, $data] = $this->dispatchJsonRequest('POST', '/auth/update-profile', [
            'avatar' => 'avatars/updated.png',
            'name' => 'Updated User',
            'extension' => '9009',
            'remark' => 'Duplicate extension',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(400, $data['code']);
        $this->assertSame('分机号码已被使用!', $data['msg']);

        $this->assertDatabaseHas('users', [
            'id' => $this->currentUser->id,
            'extension' => '8001',
        ]);
    }

    public function test_it_resets_password_and_revokes_tokens(): void
    {
        $this->assertApplicationRouteExists('auth/reset-password', 'POST', AuthController::class.'@resetPassword');

        DB::table('personal_access_tokens')->insert([
            [
                'tokenable_type' => User::class,
                'tokenable_id' => $this->currentUser->id,
                'name' => 'web',
                'token' => str_repeat('a', 64),
                'abilities' => json_encode(['*'], JSON_THROW_ON_ERROR),
                'last_used_at' => null,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tokenable_type' => User::class,
                'tokenable_id' => $this->currentUser->id,
                'name' => 'mobile',
                'token' => str_repeat('b', 64),
                'abilities' => json_encode(['*'], JSON_THROW_ON_ERROR),
                'last_used_at' => null,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        [$response, $data] = $this->dispatchJsonRequest('POST', '/auth/reset-password', [
            'old' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertSame('密码修改成功!', $data['msg']);

        $freshUser = User::query()->findOrFail($this->currentUser->id);
        $tokenCount = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $this->currentUser->id)
            ->count();

        $this->assertTrue(Hash::check('new-password-123', $freshUser->password));
        $this->assertFalse(Hash::check('old-password', $freshUser->password));
        $this->assertSame(0, $tokenCount);
    }

    public function test_it_binds_totp_for_the_current_user(): void
    {
        $this->assertApplicationRouteExists('auth/secret', 'POST', AuthController::class.'@postSecret');

        DB::table('users')->where('id', $this->currentUser->id)->update([
            'secret' => null,
            'updated_at' => now(),
        ]);

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $code = $google2fa->getCurrentOtp($secret);

        [$response, $data] = $this->dispatchJsonRequest('POST', '/auth/secret', [
            'secret' => $secret,
            'code' => $code,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);

        $this->assertDatabaseHas('users', [
            'id' => $this->currentUser->id,
            'secret' => $secret,
        ]);
    }

    public function test_it_rejects_totp_clear_when_force_enable_is_on(): void
    {
        $this->assertApplicationRouteExists('auth/clear-secret', 'GET', AuthController::class.'@clearSecret');

        DB::table('parameters')->where('name', 'cywebos_force_enable_google_authenticator')->update([
            'value' => 'true',
            'updated_at' => now(),
        ]);

        [$response, $data] = $this->dispatchJsonRequest('GET', '/auth/clear-secret');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(400, $data['code']);
        $this->assertSame('系统已开启动态口令强制验证，当前账号不允许解绑', $data['msg']);

        $this->assertDatabaseHas('users', [
            'id' => $this->currentUser->id,
            'secret' => 'JBSWY3DPEHPK3PXP',
        ]);
    }

    public function test_it_returns_only_the_current_users_login_logs(): void
    {
        $this->assertApplicationRouteExists('auth/login-logs', 'POST', AuthController::class.'@loginLogs');

        DB::table('users_login')->insert([
            [
                'id' => 1,
                'user_id' => $this->currentUser->id,
                'type' => 1,
                'ip' => '127.0.0.1',
                'country' => '中国',
                'province' => '上海',
                'city' => '上海',
                'browser' => 'Chrome',
                'platform' => 'Windows',
                'fingerprint' => 'current-log-1',
                'remark' => '当前用户日志1',
                'created_at' => '2026-03-20 10:00:00',
                'updated_at' => '2026-03-20 10:00:00',
            ],
            [
                'id' => 2,
                'user_id' => $this->currentUser->id,
                'type' => 1,
                'ip' => '127.0.0.2',
                'country' => '中国',
                'province' => '上海',
                'city' => '上海',
                'browser' => 'Edge',
                'platform' => 'Windows',
                'fingerprint' => 'current-log-2',
                'remark' => '当前用户日志2',
                'created_at' => '2026-03-21 10:00:00',
                'updated_at' => '2026-03-21 10:00:00',
            ],
            [
                'id' => 3,
                'user_id' => $this->otherUser->id,
                'type' => 1,
                'ip' => '127.0.0.3',
                'country' => '中国',
                'province' => '北京',
                'city' => '北京',
                'browser' => 'Firefox',
                'platform' => 'macOS',
                'fingerprint' => 'other-log-1',
                'remark' => '其他用户日志',
                'created_at' => '2026-03-22 10:00:00',
                'updated_at' => '2026-03-22 10:00:00',
            ],
        ]);

        [$response, $data] = $this->dispatchJsonRequest('POST', '/auth/login-logs', [
            'page' => 1,
            'rows' => 10,
            'user_id' => $this->otherUser->id,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertSame(2, data_get($data, 'data.total'));
        $this->assertCount(2, data_get($data, 'data.rows', []));
        $this->assertSame('PC端', data_get($data, 'data.rows.0.type_text'));

        $rows = data_get($data, 'data.rows');

        $this->assertIsArray($rows);
        $this->assertCount(2, $rows);
        $this->assertSame([2, 1], array_column($rows, 'id'));
        $this->assertSame(
            [$this->currentUser->id, $this->currentUser->id],
            array_column($rows, 'user_id')
        );
    }

    private function mockAuthUser(): void
    {
        Auth::shouldReceive('user')
            ->zeroOrMoreTimes()
            ->andReturnUsing(fn () => User::query()->find($this->currentUser->id));
    }

    private function seedBaseData(): void
    {
        DB::table('department')->insert([
            ['id' => 1, 'name' => '运营部', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '客服部', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('roles')->insert([
            [
                'id' => 1,
                'slug' => 'consultant',
                'name' => '咨询师',
                'permissions' => json_encode(['customer.view' => true], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'slug' => 'manager',
                'name' => '管理员',
                'permissions' => json_encode(['superuser' => true], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('parameters')->insert([
            'name' => 'cywebos_force_enable_google_authenticator',
            'value' => 'false',
            'type' => 'boolean',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('admin_parameters')->insert([
            'name' => 'oem_system_name',
            'value' => 'HIS Test',
            'type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            [
                'id' => 100,
                'email' => 'current@example.com',
                'password' => Hash::make('old-password'),
                'name' => 'Current User',
                'avatar' => 'avatars/current.png',
                'remark' => 'Current remark',
                'extension' => '8001',
                'last_login' => '2026-03-24 09:10:11',
                'banned' => 0,
                'department_id' => 1,
                'secret' => 'JBSWY3DPEHPK3PXP',
                'permissions' => json_encode([], JSON_THROW_ON_ERROR),
                'keyword' => 'current,current user',
                'created_at' => '2026-03-01 08:00:00',
                'updated_at' => '2026-03-01 08:00:00',
            ],
            [
                'id' => 200,
                'email' => 'other@example.com',
                'password' => Hash::make('other-password'),
                'name' => 'Other User',
                'avatar' => 'avatars/other.png',
                'remark' => 'Other remark',
                'extension' => '9009',
                'last_login' => '2026-03-23 10:00:00',
                'banned' => 0,
                'department_id' => 2,
                'secret' => null,
                'permissions' => json_encode([], JSON_THROW_ON_ERROR),
                'keyword' => 'other,other user',
                'created_at' => '2026-03-02 08:00:00',
                'updated_at' => '2026-03-02 08:00:00',
            ],
        ]);

        DB::table('role_users')->insert([
            ['user_id' => 100, 'role_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 200, 'role_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->currentUser = User::query()->findOrFail(100);
        $this->otherUser = User::query()->findOrFail(200);
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->text('remark')->nullable();
            $table->string('extension')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->boolean('banned')->default(false);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('secret')->nullable();
            $table->json('permissions')->nullable();
            $table->string('keyword')->nullable();
            $table->timestamps();
        });

        Schema::create('department', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('parameters', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_parameters', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('name');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('role_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('users_login', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('type')->default(1);
            $table->string('ip')->nullable();
            $table->string('country')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('fingerprint')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
        });
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

    private function dispatchJsonRequest(string $method, string $uri, array $payload = []): array
    {
        $request = Request::create($uri, $method, $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        app()->instance('request', $request);

        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return [$response, $data];
    }
}
