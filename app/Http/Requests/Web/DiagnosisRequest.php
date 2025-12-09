<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosisRequest extends FormRequest
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
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'category_id' => $this->input('category_id'),
            'name'        => $this->input('name'),
            'code'        => $this->input('code'),
        ];
    }

    /**
     * 获取create方法的验证规则
     *
     * @return array
     */
    private function getCreateRules(): array
    {
        return [
            'category_id' => 'required|exists:diagnosis_category,id',
            'name'        => 'required'
        ];
    }

    /**
     * 获取create方法的错误消息
     *
     * @return array
     */
    private function getCreateMessages(): array
    {
        return [
            'category_id.required' => '[诊断分类]不能为空!',
            'category_id.exists'   => '[诊断分类]找不到!',
            'name.required'        => '[诊断名称]不能为空!',
        ];
    }

    /**
     * 获取update方法的验证规则
     *
     * @return array
     */
    private function getUpdateRules(): array
    {
        return [
            'id'          => 'required|exists:diagnosis',
            'name'        => 'required',
            'category_id' => 'required|exists:diagnosis_category,id',
        ];
    }

    /**
     * 获取update方法的错误消息
     *
     * @return array
     */
    private function getUpdateMessages(): array
    {
        return [
            'id.required'          => '缺少id参数',
            'id.exists'            => '没有找到数据',
            'name.required'        => '[诊断名称]不能为空',
            'category_id.required' => '[诊断分类]不能为空',
            'category_id.exists'   => '[诊断分类]不存在'
        ];
    }

    /**
     * 获取remove方法的验证规则
     *
     * @return array
     */
    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:diagnosis'
        ];
    }

    /**
     * 获取remove方法的错误消息
     *
     * @return array
     */
    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '没有找到数据',
        ];
    }
}
