<?php

namespace App\Http\Requests\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
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
            'create' => [
                'name'    => 'required|unique:warehouse',
                'users'   => 'array',
                'users.*' => 'integer|exists:users,id'
            ],
            'update' => [
                'id'      => 'required|exists:warehouse',
                'name'    => 'required|unique:warehouse,name,' . $this->input('id') . ',id',
                'users'   => 'array',
                'users.*' => 'integer|exists:users,id'
            ],
            'enable', 'disable' => [
                'id' => 'required|exists:warehouse,id'
            ],
            'remove' => [
                'id' => [
                    'required',
                    'exists:warehouse',
                    function ($attribute, $value, $fail) {
                        if (DB::table('purchase')->where('warehouse_id', $value)->count()) {
                            $fail('《采购进货》已经使用,无法删除');
                        }
                    }
                ]
            ],
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => [
                'name . required' => '仓库信息不能为空！',
                'name . unique'   => '仓库信息已存在！',
                'users . array'   => '仓库负责人格式错误！',
                'users .*.exists' => '仓库负责人不存在！'
            ],
            'update' => [
                'id . required'   => '仓库信息不能为空！',
                'id . exists'     => '仓库信息不存在！',
                'name . required' => '仓库信息不能为空！',
                'name . unique'   => '仓库信息已存在！',
            ],
            default => []
        };
    }

    public function formData(): array
    {
        return [
            'name'   => $this->input('name'),
            'remark' => $this->input('remark')
        ];
    }
}
