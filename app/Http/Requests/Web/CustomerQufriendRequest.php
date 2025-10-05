<?php

namespace App\Http\Requests\Web;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerQufriendRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'info' => $this->getInfoRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'info' => $this->getInfoMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
        };
    }

    private function getCreateRules(): array
    {
        return [
            'customer_id'         => 'required|exists:customer,id',
            'related_customer_id' => [
                'required',
                'exists:customer,id',
                'not_in:' . $this->input('customer_id'),
                Rule::unique('customer_qufriends')->where(function ($query) {
                    return $query->where('customer_id', $this->input('customer_id'))->where('qufriend_id', request('qufriend_id'));
                }),
            ],
            'qufriend_id'         => 'required|integer|exists:qufriends,id',
            'remark'              => 'nullable|string|max:255',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'customer_id.required'         => '客户ID不能为空',
            'customer_id.exists'           => '客户ID不存在',
            'related_customer_id.required' => '亲友ID不能为空',
            'related_customer_id.exists'   => '亲友ID不存在',
            'related_customer_id.unique'   => '亲友关系已存在',
            'related_customer_id.not_in'   => '亲友ID不能为客户ID',
            'qufriend_id.required'         => '亲友关系ID不能为空',
            'qufriend_id.integer'          => '亲友关系ID必须为整数',
            'qufriend_id.exists'           => '亲友关系ID不存在',
            'remark.string'                => '备注必须为字符串',
            'remark.max'                   => '备注最大长度为255',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'          => 'required|integer|exists:customer_qufriends,id',
            'qufriend_id' => 'required|integer|exists:qufriends,id',
            'remark'      => 'nullable|string|max:255',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'          => 'ID不能为空',
            'id.integer'           => 'ID必须为整数',
            'id.exists'            => 'ID不存在',
            'qufriend_id.required' => '亲友关系ID不能为空',
            'qufriend_id.integer'  => '亲友关系ID必须为整数',
            'qufriend_id.exists'   => '亲友关系ID不存在',
            'remark.string'        => '备注必须为字符串',
            'remark.max'           => '备注最大长度为255',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|integer|exists:customer_qufriends,id',
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => 'ID不能为空',
            'id.integer'  => 'ID必须为整数',
            'id.exists'   => 'ID不存在',
        ];
    }

    /**
     * 表单信息
     * @return array
     */
    public function formData(): array
    {
        $data = [
            'customer_id'         => $this->input('customer_id'),
            'related_customer_id' => $this->input('related_customer_id'),
            'qufriend_id'         => $this->input('qufriend_id'),
            'remark'              => $this->input('remark'),
            'create_user_id'      => user()->id,
        ];
        if (request()->route()->getActionMethod() === 'update') {
            unset($data['customer_id']);
            unset($data['create_user_id']);
            unset($data['related_customer_id']);
        }
        return $data;
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|integer|exists:customer_qufriends,id',
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => 'ID不能为空',
            'id.integer'  => 'ID必须为整数',
            'id.exists'   => 'ID不存在',
        ];
    }
}
