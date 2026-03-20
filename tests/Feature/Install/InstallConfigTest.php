<?php

namespace Tests\Feature\Install;

use Tests\TestCase;

class InstallConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    /**
     * GET /install/config 返回 MySQL 和 Redis 默认配置
     */
    public function test_get_config_returns_db_and_redis_defaults(): void
    {
        $response = $this->getJson('/install/config');

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure([
                'data' => [
                    'db_host',
                    'db_port',
                    'db_database',
                    'db_username',
                    'db_password',
                    'redis_host',
                    'redis_port',
                    'redis_password',
                ],
            ]);
    }

    /**
     * Redis 密码为 null 时接口返回空字符串
     */
    public function test_redis_password_null_returns_empty_string(): void
    {
        $response = $this->getJson('/install/config');

        $response->assertOk()->assertJsonPath('code', 200);
        $data = $response->json('data');

        // redis_password 不应为字面量 "null"
        $this->assertNotEquals('null', $data['redis_password']);
        $this->assertIsString($data['redis_password']);
    }

    /**
     * POST /install/start 缺少 redis_host 时返回验证错误
     * 注意：项目的 ValidationException 处理器返回 HTTP 200 + body {code: 400, msg: "..."}
     */
    public function test_start_requires_redis_host(): void
    {
        $payload = [
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'saas',
            'db_username' => 'root',
            'db_password' => 'password',
            // redis_host 故意缺失
            'redis_port' => '6379',
            'central_domain' => 'http://localhost',
            'central_admin_path' => 'admin',
            'admin_username' => 'admin@test.com',
            'admin_password' => 'secret',
        ];

        $response = $this->postJson('/install/start', $payload);

        $response->assertOk()->assertJsonPath('code', 400);
        $this->assertStringContainsString('Redis 主机', $response->json('msg'));
    }

    /**
     * POST /install/start 缺少 redis_port 时返回验证错误
     */
    public function test_start_requires_redis_port(): void
    {
        $payload = [
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'saas',
            'db_username' => 'root',
            'db_password' => 'password',
            'redis_host' => '127.0.0.1',
            // redis_port 故意缺失
            'central_domain' => 'http://localhost',
            'central_admin_path' => 'admin',
            'admin_username' => 'admin@test.com',
            'admin_password' => 'secret',
        ];

        $response = $this->postJson('/install/start', $payload);

        $response->assertOk()->assertJsonPath('code', 400);
        $this->assertStringContainsString('Redis 端口', $response->json('msg'));
    }
}
