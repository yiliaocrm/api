<?php

namespace App\Rules\Cashier;

use App\Models\Cashier;
use Illuminate\Contracts\Validation\Rule;

class ChargeRule implements Rule
{
    protected $pay;
    protected $detail;
    protected $message;

    public function __construct($pay, $detail)
    {
        $this->pay    = collect($pay);
        $this->detail = collect($detail);
    }

    /**
     * 收费验证规则
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $type = Cashier::query()->find($value)->cashierable_type;

        if ($type == 'App\Models\Consultant') {
            return $this->consultantPasses($attribute, $value);
        }

        if ($type == 'App\Models\Outpatient') {
            return $this->outpatientPasses($attribute, $value);
        }

        if ($type == 'App\Models\CashierRefund') {
            return $this->refundPasses($attribute, $value);
        }

        if ($type == 'App\Models\Erkai') {
            return $this->erkaiPasses($attribute, $value);
        }
    }

    /**
     * 现场咨询验证
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function consultantPasses($attribute, $value): bool
    {
        # 判断收费账户是否重复
        if ($this->pay->pluck('accounts_id')->unique()->count() != $this->pay->pluck('accounts_id')->count()) {
            $this->message = '收款账户不能重复!';
            return false;
        }

        // 支付方式大于1, 并且 其中有金额为0
        if ($this->pay->pluck('accounts_id')->count() > 1 && $this->pay->where('income', 0)->count()) {
            $this->message = '【支付方式】收款金额不能为0!';
            return false;
        }

        if ($this->detail->where('product_id', 1)->count() > 1) {
            $this->message = '【预收费用】重复!';
            return false;
        }

        # 有预收费没有收款信息
        if ($this->detail->where('product_id', 1)->count() && !$this->pay->count()) {
            $this->message = '【预收费用】项目必须收费!';
            return false;
        }

        # 预收费用 大于 实收费用
        if ($this->detail->where('product_id', 1)->count() && $this->detail->where('product_id', 1)->sum('payable') > $this->pay->where('accounts_id', '<>', 1)->sum('income')) {
            $this->message = '【实收金额】必须大于【预收费用】!';
            return false;
        }

        # 余额支付 大于 账户实际余额
        if ($this->pay->where('accounts_id', 1)->sum('income') > Cashier::query()->find($value)->customer->balance) {
            $this->message = '账户余额不够支付';
            return false;
        }

        return true;
    }

    /**
     * 医生门诊验证
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function outpatientPasses($attribute, $value): bool
    {
        # 判断收费账户是否重复
        if ($this->pay->pluck('accounts_id')->unique()->count() != $this->pay->pluck('accounts_id')->count()) {
            $this->message = '收款账户不能重复!';
            return false;
        }

        # 余额支付 大于 账户实际余额
        if ($this->pay->where('accounts_id', 1)->sum('income') > Cashier::query()->find($value)->customer->balance) {
            $this->message = '账户余额不够支付';
            return false;
        }

        # 后续是否加入处方验证

        return true;
    }

    /**
     * 退款验证
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function refundPasses($attribute, $value): bool
    {
        // 判断金额
        if ($this->pay->sum('income') != $this->detail->sum('amount')) {
            $this->message = '《支付金额》与《合计应收》不一致!';
            return false;
        }

        // 支付方式大于1, 并且 其中有金额为0
        if ($this->pay->pluck('accounts_id')->count() > 1 && $this->pay->where('income', 0)->count()) {
            $this->message = '【支付方式】收款金额不能为0!';
            return false;
        }

        return true;
    }

    /**
     * 二开零售验证
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function erkaiPasses($attribute, $value): bool
    {
        # 判断收费账户是否重复
        if ($this->pay->pluck('accounts_id')->unique()->count() != $this->pay->pluck('accounts_id')->count()) {
            $this->message = '收款账户不能重复!';
            return false;
        }

        // 支付方式大于1, 并且 其中有金额为0
        if ($this->pay->pluck('accounts_id')->count() > 1 && $this->pay->where('income', 0)->count()) {
            $this->message = '【支付方式】收款金额不能为0!';
            return false;
        }

        if ($this->detail->where('product_id', 1)->count() > 1) {
            $this->message = '【预收费用】重复!';
            return false;
        }

        # 有预收费没有收款信息
        if ($this->detail->where('product_id', 1)->count() && !$this->pay->count()) {
            $this->message = '【预收费用】项目必须收费!';
            return false;
        }

        # 预收费用 大于 实收费用
        if ($this->detail->where('product_id', 1)->count() && $this->detail->where('product_id', 1)->sum('payable') > $this->pay->where('accounts_id', '<>', 1)->sum('income')) {
            $this->message = '【实收金额】必须大于【预收费用】!';
            return false;
        }

        # 余额支付 大于 账户实际余额
        if ($this->pay->where('accounts_id', 1)->sum('income') > Cashier::query()->find($value)->customer->balance) {
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
    public function message(): string
    {
        return $this->message;
    }
}
