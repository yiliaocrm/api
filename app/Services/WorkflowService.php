<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WorkflowService
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    protected int $timeout;

    protected string $cacheKey = 'workflow_auth_token';

    public function __construct()
    {
        $this->baseUrl = rtrim(admin_parameter('workflow_api_url'), '/');
        $this->timeout = admin_parameter('workflow_timeout') ?? 30;
        $this->username = admin_parameter('workflow_username');
        $this->password = admin_parameter('workflow_password');
    }

    /**
     * 工作流接口登录，获取并缓存令牌
     *
     * @throws Exception
     */
    public function login(): array
    {
        $response = Http::timeout($this->timeout)
            ->post("{$this->baseUrl}/api/v1/login", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

        if (! $response->successful()) {
            throw new Exception('工作流API身份验证失败: '.$response->body());
        }

        $data = $response->json();

        if (! isset($data['token']) || ! isset($data['expiresAt'])) {
            throw new Exception('无效的身份验证响应: 缺少token或expiresAt');
        }

        $tokenData = [
            'token' => $data['token'],
            'expiresAt' => $data['expiresAt'],
        ];

        // 计算TTL并添加60秒缓冲
        $ttl = max(0, $data['expiresAt'] - time() - 60);

        // 缓存令牌
        Cache::put($this->cacheKey, $tokenData, $ttl);

        return $tokenData;
    }

    /**
     * 获取分页的规则链列表
     *
     * @param  array  $params  查询参数 (size, page, keywords, disabled, root)
     *
     * @throws Exception
     */
    public function getRuleChains(array $params = []): array
    {
        $this->ensureAuthenticated();

        $response = $this->client()->get('/api/v1/rules', $params);

        if (! $response->successful()) {
            throw new Exception('获取规则链失败: '.$response->body());
        }

        return $response->json();
    }

    /**
     * 保存规则链
     *
     * @param  string  $id  规则链ID
     * @param  array  $data  规则链数据 (包含 ruleChain 和 metadata)
     * @return array
     *
     * @throws Exception
     */
    public function saveRuleChain(string $id, array $data): array
    {
        $this->ensureAuthenticated();

        $response = $this->client()->post("/api/v1/rules/{$id}", $data);

        if (! $response->successful()) {
            throw new Exception('保存规则链失败: '.$response->body());
        }

        return $response->json();
    }

    /**
     * 删除规则链
     *
     * @param  string  $id  规则链ID
     * @return array
     *
     * @throws Exception
     */
    public function deleteRuleChain(string $id): array
    {
        $this->ensureAuthenticated();

        $response = $this->client()->delete("/api/v1/rules/{$id}");

        if (! $response->successful()) {
            throw new Exception('删除规则链失败: '.$response->body());
        }

        return $response->json();
    }

    /**
     * 获取当前有效的令牌 (自动刷新)
     *
     * @throws Exception
     */
    public function getToken(): string
    {
        $this->ensureAuthenticated();
        $tokenData = Cache::get($this->cacheKey);

        return $tokenData['token'];
    }

    /**
     * 清除缓存的令牌
     */
    public function clearToken(): void
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * 确保存在有效的令牌 (需要时自动刷新)
     *
     * @throws Exception
     */
    protected function ensureAuthenticated(): void
    {
        $tokenData = Cache::get($this->cacheKey);

        // 如果令牌不存在或已过期，重新登录
        if (! $tokenData || ! isset($tokenData['token']) || ! isset($tokenData['expiresAt'])) {
            $this->login();

            return;
        }

        // 检查令牌是否即将过期 (60秒内)
        if ($tokenData['expiresAt'] - time() <= 60) {
            $this->login();
        }
    }

    /**
     * 获取已认证的HTTP客户端
     *
     * @throws Exception
     */
    protected function client(): PendingRequest
    {
        $tokenData = Cache::get($this->cacheKey);

        if (! $tokenData || ! isset($tokenData['token'])) {
            throw new Exception('没有有效的身份验证令牌可用');
        }

        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->withToken($tokenData['token'])
            ->acceptJson();
    }
}
