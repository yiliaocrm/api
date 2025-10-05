<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Google\Authenticator\GoogleAuthenticator;

/**
 * 验证动态口令
 */
class GoogleAuthenticatorRule implements Rule
{
    /**
     * 密钥
     * @var string
     */
    protected string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $g = new GoogleAuthenticator();
        if (!$g->checkCode($this->secret, $value)) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return '动态口令错误！';
    }
}
