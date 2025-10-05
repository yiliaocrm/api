<?php

namespace App\Http\Requests\Web;

use App\Models\Customer;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerJobRequest extends FormRequest
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
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:customer_job'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:customer_job',
            'name' => [
                'required',
                Rule::unique('customer_job')->ignore($this->input('id')),
            ]
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:customer_job',
                'not_in:1,2,3,4,5,6',
                function ($attribute, $value, $fail) {
                    if (Customer::query()->where('job_id', $value)->count()) {
                        $fail('《顾客信息》表中已经使用,无法删除!');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '请输入名称',
            'name.unique'   => "《{$this->input('name')}》已存在！"
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数!',
            'id.exists'     => '没有找到id参数',
            'name.required' => '名称不能为空!',
            'name.unique'   => "《{$this->input('name')}》已存在!"
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到数据!',
            'id.not_in'   => '系统自带数据无法删除!'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'   => $this->input('name'),
            'remark' => $this->input('remark')
        ];
    }
}
