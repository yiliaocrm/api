<?php

namespace App\Http\Requests\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
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
            'name' => 'required|unique:department'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:department',
            'name' => 'required|unique:department,name,' . $this->input('id')
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:department',
                'not_in:1,2',
                // 闭包验证department_id是否被使用
                function ($attribute, $value, $fail) {
                    $where = [
                        'department_id' => $value
                    ];

                    if (DB::table('users')->where($where)->count()) {
                        $fail('【员工表】已用该数据,无法直接删除!');
                        return;
                    }

                    if (DB::table('reservation')->where($where)->count()) {
                        $fail('【网电咨询】已用该数据,无法直接删除!');
                        return;
                    }

                    if (DB::table('reception')->where($where)->count()) {
                        $fail('【分诊接待】已用该数据,无法直接删除!');
                        return;
                    }

                    if (DB::table('customer')->where($where)->count()) {
                        $fail('【顾客信息】已用该数据,无法直接删除!');
                        return;
                    }

                    if (DB::table('cashier_detail')->where($where)->count()) {
                        $fail('【收费列表】已用该数据,无法直接删除!');
                        return;
                    }

                    if (DB::table('appointments')->where($where)->count()) {
                        $fail('【预约表】已用该数据,无法直接删除!');
                        return;
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
            'name.required' => '科室信息不能为空！',
            'name.unique'   => '科室信息已存在！'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少必要参数',
            'id.exists'     => '没有找到数据',
            'name.required' => '科室信息不能为空！',
            'name.unique'   => '科室信息已存在！'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.exists' => '没有找到数据',
            'id.not_in' => '基础信息无法删除'
        ];
    }

    public function formData(): array
    {
        return [
            'store_id' => store()->id,
            'name'     => $this->input('name'),
            'primary'  => $this->input('primary', 0),
            'remark'   => $this->input('remark')
        ];
    }
}
