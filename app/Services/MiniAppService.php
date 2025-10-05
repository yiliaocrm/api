<?php

namespace App\Services;

use EasyWeChat\MiniApp\Application;
use EasyWeChat\Kernel\HttpClient\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MiniAppService
{
    protected Application $app;

    public function __construct()
    {
        $this->app = new Application([
            'app_id'  => parameter('wechat_mini_app_appid'),
            'secret'  => parameter('wechat_mini_app_secret'),
            'token'   => parameter('wechat_mini_app_token'),
            'aes_key' => parameter('wechat_mini_app_aes_key'),
        ]);
        // 设置easywechat的缓存为laravel自带的缓存
        $this->app->setCache(app('cache.store'));
    }

    public function codeToSession(string $code): array
    {
        $utils = $this->app->getUtils();
        return $utils->codeToSession($code);
    }

    /**
     * 获取用户手机号
     * @param string $code
     * @return ResponseInterface|Response
     * @throws TransportExceptionInterface
     */
    public function getUserPhoneNumber(string $code): ResponseInterface|Response
    {
        $params = [
            'code' => $code,
        ];
        return $this->app->getClient()->postJson('wxa/business/getuserphonenumber', $params);
    }
}
