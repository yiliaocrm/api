<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class IntegralRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexRules(),
            'adjust' => $this->getAdjustRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'adjust' => $this->getAdjustMessages(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'rows'       => 'nullable|integer',
            'sort'       => 'nullable|string',
            'order'      => 'nullable|string|in:asc,desc',
            'type'       => 'nullable|array',
            'keyword'    => 'nullable|string|max:50',
            'created_at' => 'nullable|array|size:2',
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'rows.integer'     => '每页条数必须为整数',
            'sort.string'      => '排序字段必须为字符串',
            'order.string'     => '排序方式必须为字符串',
            'order.in'         => '排序方式错误',
            'type.array'       => '积分类型格式错误',
            'keyword.string'   => '关键字必须为字符串',
            'keyword.max'      => '关键字长度不能超过50个字符',
            'created_at.array' => '创建时间格式错误',
            'created_at.size'  => '创建时间格式错误',
        ];
    }

    private function getAdjustRules(): array
    {
        return [
            'customer_id' => 'required|exists:customer,id',
            'action'      => 'required|in:addition,subtraction',
            'number'      => 'required|numeric',
        ];
    }

    private function getAdjustMessages(): array
    {
        return [
            'customer_id.required' => '缺少customer_id参数!',
            'customer_id.exists'   => '顾客信息不存在!',
            'action.required'      => '积分操作类型不能为空!',
            'action.in'            => '积分操作类型错误',
            'number.required'      => ($this->input('action') == 'addition') ? '增加积分不能为空!' : '扣减积分不能为空!',
        ];
    }

    /**
     * 更新顾客主表积分
     * @param $customer
     * @return array
     */
    public function formData($customer): array
    {
        $addition = $this->input('action') == 'addition';
        $integral = $addition ? $this->input('number') : -1 * abs($this->input('number'));
        return [
            'integral' => $customer->integral + $integral
        ];
    }

    /**
     * 扣减积分数据
     * @param $customer
     * @return array
     */
    public function integralsData($customer): array
    {
        $addition = $this->input('action') == 'addition';
        $integral = $addition ? $this->input('number') : -1 * abs($this->input('number'));
        return [
            'type'     => $addition ? 5 : 6,    // 积分类型:5:手工赠送,6:手工扣减
            'type_id'  => $customer->id,
            'before'   => $customer->integral,
            'integral' => $integral,
            'after'    => $customer->integral + $integral,
            'remark'   => $this->input('remark'),
            'data'     => null
        ];
    }
}
