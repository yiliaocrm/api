<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfigRequest;
use App\Jobs\SyncDistAssetsJob;
use App\Models\Admin\AdminParameter;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

class ConfigController extends Controller
{
    /**
     * 基础配置
     */
    public function load(): JsonResponse
    {
        $config = AdminParameter::query()->get();

        return response_success($config);
    }

    /**
     * 保存配置
     */
    public function save(ConfigRequest $request): JsonResponse
    {
        $parameters = $request->input('config');

        foreach ($parameters as $param) {
            $parameter = AdminParameter::query()->find($param['name']);
            if ($parameter) {
                $parameter->value = $param['value'];
                $parameter->save();
            }
        }

        // 清除缓存
        cache()->forget('admin_parameters');

        return response_success();
    }

    /**
     * 生成2FA密钥
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function secret(): JsonResponse
    {
        $google2fa = new Google2FA;
        $secretKey = $google2fa->generateSecretKey();
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            admin_parameter('oem_system_name'),
            admin()->email,
            $secretKey
        );

        return response_success([
            'secret' => $secretKey,
            'qrcode' => $qrCodeUrl,
        ]);
    }

    /**
     * 验证2FA密钥
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function verify(ConfigRequest $request): JsonResponse
    {
        $secret = $request->input('secret');
        $code = $request->input('code');

        $google2fa = new Google2FA;
        $isValid = $google2fa->verifyKey($secret, $code);

        if ($isValid) {
            return response_success(msg: '验证成功');
        }

        return response_error(msg: '验证码错误，请重试');
    }

    /**
     * 一键同步public/dist目录到云存储
     */
    public function distSync(ConfigRequest $request): JsonResponse
    {
        dispatch(new SyncDistAssetsJob);

        return response_success(msg: '同步任务已提交');
    }
}
