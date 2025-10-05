<?php

namespace App\Http\Requests\Cashier;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CancelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => [
                'required',
                Rule::exists('cashier')->where(function ($query) {
                    $query->where('status', 1);
                })
            ]
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '收费单状态错误!',
        ];
    }

    /**
     * 更新数据
     * @param $cashier
     * @return array
     */
    public function updateData($cashier)
    {
        // 退[现场咨询]单
        if ($cashier->cashierable_type == 'App\Models\Consultant') {
            return $this->handleConsultant($cashier);
        }
        // 退[退款申请]单
        if ($cashier->cashierable_type == 'App\Models\CashierRefund') {
            return $this->handleCashierRefund($cashier);
        }
        // 退[二开零购]单
        if ($cashier->cashierable_type == 'App\Models\Erkai') {
            return $this->handleErkai($cashier);
        }
    }

    /**
     * 现场咨询订单
     * @param $cashier
     * @return array
     */
    public function handleConsultant($cashier)
    {
        // [现场咨询单]状态修改
        $r = $cashier->cashierable->orders()->where('status', 3)->count();
        if ($r) {
            $cashier->cashierable->update(['status' => 2]); // 成交
        } else {
            $cashier->cashierable->update(['status' => 1]); // 未成交
        }

        // 根据cashier表中detail字段 更新 现场咨询开单表 状态为退单
        $cashier->cashierable->orders()->whereIn('id', collect($cashier->detail)->pluck('id'))->update([
            'status' => 4
        ]);

        return [
            'status'   => 3,
            'operator' => user()->id,
        ];
    }

    /**
     * 退款申请单
     * @param $cashier
     * @return array
     */
    public function handleCashierRefund($cashier)
    {
        // 更新退款申请单
        $cashier->cashierable->update([
            'status' => 4
        ]);

        return [
            'status'   => 3,
            'operator' => user()->id,
        ];
    }

    /**
     * 退二开零购单
     * @param $cashier
     * @return array
     */
    public function handleErkai($cashier)
    {
        // 更新二开表为"退单"
        $cashier->cashierable->update([
            'status' => 3
        ]);

        // 更新二开明细表为"退单"
        $cashier->cashierable->details()->update([
            'status' => 4
        ]);

        return [
            'status'   => 3,
            'operator' => user()->id,
        ];
    }
}
