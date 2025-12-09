<?php

namespace App\Http\Requests\Web;

use App\Models\Customer;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerLevelRequest extends FormRequest
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
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    /**
     * 新增会员等级验证规则
     *
     * @return array
     */
    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:customer_level'
        ];
    }

    /**
     * 新增会员等级错误消息
     *
     * @return array
     */
    private function getCreateMessages(): array
    {
        return [
            'name.required' => '请输入名称',
            'name.unique'   => "《{$this->name}》已存在！"
        ];
    }

    /**
     * 更新会员等级验证规则
     *
     * @return array
     */
    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:customer_level',
            'name' => [
                'required',
                Rule::unique('customer_level')->ignore($this->id),
            ]
        ];
    }

    /**
     * 更新会员等级错误消息
     *
     * @return array
     */
    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数!',
            'id.exists'     => '没有找到id参数',
            'name.required' => '名称不能为空!',
            'name.unique'   => "《{$this->name}》已存在!"
        ];
    }

    /**
     * 删除会员等级验证规则
     *
     * @return array
     */
    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:customer_level',
                function ($attribute, $value, $fail) {
                    $count = Customer::where('level_id', $value)->count();
                    if ($count) {
                        $fail('会员等级已经被使用,无法删除');
                    }
                }
            ]
        ];
    }

    /**
     * 删除会员等级错误消息
     *
     * @return array
     */
    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到数据!'
        ];
    }
}
