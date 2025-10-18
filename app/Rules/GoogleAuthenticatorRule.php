<?php

namespace App\Rules;

use Closure;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use Illuminate\Contracts\Validation\ValidationRule;

class GoogleAuthenticatorRule implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 获取密钥
        $secret = admin_parameter('tfa_secret');

        // 如果密钥不存在，验证失败
        if (empty($secret)) {
            $fail('双重验证密钥未配置,请先扫码绑定!');
            return;
        }

        // 验证 TOTP 码格式
        if (!is_numeric($value) || strlen($value) !== 6) {
            $fail('验证码格式不正确，应为6位数字');
            return;
        }

        // 使用 Google2FA 验证
        $google2fa = new Google2FA();

        // 验证当前 TOTP 码是否有效（允许前后各一个时间窗口，防止时间误差）
        $valid = $google2fa->verifyKey($secret, $value, 1);

        if (!$valid) {
            $fail('验证码错误或已过期');
        }
    }
}
