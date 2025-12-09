<?php

namespace App\Http\Requests\Web;

use App\Models\Diagnosis;
use App\Models\DiagnosisCategory;
use Illuminate\Foundation\Http\FormRequest;

class DiagnosisCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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

    /**
     * Get the error messages for the defined validation rules.
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
     * 创建分类的验证规则
     */
    private function getCreateRules(): array
    {
        return [
            'name'     => 'required',
            'parentid' => 'required|exists:diagnosis_category,id'
        ];
    }

    /**
     * 创建分类的错误消息
     */
    private function getCreateMessages(): array
    {
        return [
            'name.required'     => '[分类名称]不能为空!',
            'parentid.required' => '[父节点]不能为空!',
            'parentid.exists'   => '[父节点]不存在!',
        ];
    }

    /**
     * 更新分类的验证规则
     */
    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:diagnosis_category',
            'name' => 'required'
        ];
    }

    /**
     * 更新分类的错误消息
     */
    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数',
            'id.exists'     => '没有找到数据',
            'name.required' => '分类名称不能为空',
        ];
    }

    /**
     * 删除分类的验证规则
     */
    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:diagnosis_category',
                function ($attribute, $value, $fail) {
                    $ids   = DiagnosisCategory::find($value)->getAllChild()->pluck('id');
                    $count = Diagnosis::whereIn('category_id', $ids)->count();
                    if ($count) {
                        $fail('诊断分类下有数据,无法删除!');
                    }
                }
            ]
        ];
    }

    /**
     * 删除分类的错误消息
     */
    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
        ];
    }
}
