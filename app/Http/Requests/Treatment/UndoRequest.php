<?php

namespace App\Http\Requests\Treatment;

use App\Models\Treatment;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UndoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                Rule::exists('treatment')->where(function ($query) {
                    $query->where('id', $this->input('id'))->where('status', 1);
                })
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '状态不正确,无法撤销!'
        ];
    }

    /**
     * 更新数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'status'       => 2,
            'undo_user_id' => user()->id
        ];
    }

    /**
     * 业绩反向操作
     * @param $cashier_id
     * @param $treatment
     * @param $reception_type
     * @return array
     */
    public function salesPerformanceData($cashier_id, $treatment, $reception_type): array
    {
        $data = [];

        if (!empty($treatment->participants)) {
            foreach ($treatment->participants as $v) {
                $data[] = [
                    'cashier_id'     => $cashier_id,
                    'customer_id'    => $treatment->customer_id,
                    'position'       => 3,  // 项目服务
                    'table_name'     => 'App\Models\Treatment',
                    'table_id'       => $treatment->id,
                    'user_id'        => $v['user_id'],
                    'reception_type' => $reception_type,
                    'package_id'     => $treatment->package_id,
                    'package_name'   => $treatment->package_name,
                    'product_id'     => $treatment->product_id,
                    'product_name'   => $treatment->product_name,
                    'goods_id'       => null,
                    'goods_name'     => null,
                    'payable'        => 0,
                    'income'         => 0,
                    'arrearage'      => $treatment->arrearage,
                    'deposit'        => 0,
                    'amount'         => -1 * abs($treatment->price),  // 计提金额
                    'rate'           => 100,
                    'remark'         => get_user_name($treatment->undo_user_id) . '<撤销划扣>'
                ];
            }
        }

        return $data;
    }

    /**
     * 更新顾客最后一次划扣时间
     * @param $treatment
     * @return array
     */
    public function lastTreatmentData($treatment): array
    {
        $record = Treatment::query()
            ->where('customer_id', $treatment->customer_id)
            ->where('id', '<>', $treatment->id)
            ->orderByDesc('created_at')
            ->first();
        return [
            'last_treatment' => $record ? $record->created_at : null
        ];
    }
}
