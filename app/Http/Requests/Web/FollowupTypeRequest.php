<?php

namespace App\Http\Requests\Web;

use App\Models\Followup;
use App\Models\FollowupTemplateDetail;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class FollowupTypeRequest extends FormRequest
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
            'name' => 'required|unique:followup_type'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:followup_type',
            'name' => [
                'required',
                Rule::unique('followup_type')->ignore($this->input('id')),
            ]
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:followup_type',
                function ($attribute, $value, $fail) {
                    if (Followup::query()->where('type', $value)->first()) {
                        $fail('回访记录里面已经使用,无法删除!');
                        return;
                    }

                    if (FollowupTemplateDetail::query()->where('followup_type_id', $value)->first()) {
                        $fail('回访模板中已在使用,无法删除!');
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
            'id.exists'   => '没有找到数据!'
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
            'icon'   => $this->input('icon'),
            'remark' => $this->input('remark')
        ];
    }
}
