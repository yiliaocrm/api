<?php

namespace App\Rules;

use Closure;
use App\Models\CustomerPhone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneRule implements ValidationRule
{
    protected ?string $customer_id;

    public function __construct(?string $customer_id = null)
    {
        $this->customer_id = $customer_id;
    }

    /**
     * 验证电话号码规则
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 如果电话号码中包含*，则跳过验证（通常用于部分隐藏的电话号码）
        if (str_contains($value, '*')) {
            return;
        }
        $this->validatePhoneFormat($value, $fail);
        $this->validatePhoneUniqueness($value, $fail);
    }

    /**
     * 验证电话号码格式
     * @param string $phone
     * @param Closure $fail
     * @return void
     */
    private function validatePhoneFormat(string $phone, Closure $fail): void
    {
        $rule = parameter('cywebos_phone_rule');
        if (!$rule) {
            return;
        }

        if (!preg_match($rule, $phone)) {
            $fail("{$phone}不是有效的联系电话");
        }
    }

    /**
     * 验证电话号码唯一性
     * @param string $phone
     * @param Closure $fail
     * @return void
     */
    private function validatePhoneUniqueness(string $phone, Closure $fail): void
    {
        // 如果未启用手机号码唯一性验证，则不进行检查
        if (!parameter('customer_phone_unique')) {
            return;
        }

        $count = CustomerPhone::query()
            ->where('phone', $phone)
            ->when($this->customer_id, fn(Builder $query) => $query->where('customer_id', '!=', $this->customer_id))
            ->first();

        if ($count) {
            $fail("手机号码 {$phone} 已被使用!");
        }
    }
}
