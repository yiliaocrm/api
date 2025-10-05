<?php

namespace App\Rules\CashierRetail;

use App\Models\Customer;
use Illuminate\Contracts\Validation\Rule;

class ChargeRule implements Rule
{
    protected $pay;
    protected $detail;
    protected $message;

    public function __construct($pay, $detail)
    {
        $this->pay = collect($pay);
        $this->detail = collect($detail);
    }

    /**
     * Determine if the validation rule passes.
     * @param string $attribute
     * @param mixed $customer_id
     * @return bool
     */
    public function passes($attribute, $customer_id)
    {
        // 判断收费账户是否重复
        if ($this->pay->pluck('accounts_id')->unique()->count() != $this->pay->pluck('accounts_id')->count()) {
            $this->message = '收款账户不能重复!';
            return false;
        }

        // 判断预收费
        if ($this->detail->where('product_id', 1)->count() > 1) {
            $this->message = '【预收费用】重复!';
            return false;
        }

        // 有预收费没有收款信息
        if ($this->detail->where('product_id', 1)->count() && !$this->pay->count()) {
            $this->message = '【预收费用】项目必须收费!';
            return false;
        }

        // 预收费用 大于 实收费用
        if ($this->detail->where('product_id', 1)->count() && $this->detail->where('product_id', 1)->sum('payable') > $this->pay->where('accounts_id', '<>', 1)->sum('income')) {
            $this->message = '【实收金额】必须大于【预收费用】!';
            return false;
        }

        // 判断账户余额
        if ($this->pay->where('accounts_id', 1)->sum('income') > Customer::find($customer_id)->balance) {
            $this->message = '账户余额不够支付';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
