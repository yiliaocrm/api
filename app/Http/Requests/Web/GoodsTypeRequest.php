<?php

namespace App\Http\Requests\Web;

use App\Models\Goods;
use App\Models\GoodsType;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class GoodsTypeRequest extends FormRequest
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

    private function getCreateRules(): array
    {
        return [
            'parentid' => 'required|integer|exists:goods_type,id',
            'name'     => 'required|string|max:255',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'parentid.required' => '父级分类不能为空！',
            'parentid.integer'  => '父级分类必须为整数！',
            'parentid.exists'   => '父级分类不存在！',
            'name.required'     => '分类名称不能为空！',
            'name.string'       => '分类名称必须为字符串！',
            'name.max'          => '分类名称不能超过255个字符！'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|integer|exists:goods_type',
            'name' => 'required|string|max:255',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数！',
            'id.exists'     => '分类不存在！',
            'name.required' => '分类名称不能为空！',
            'name.string'   => '分类名称必须为字符串！',
            'name.max'      => '分类名称不能超过255个字符！'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:goods_type',
                function ($attribute, $id, $fail) {
                    $type = GoodsType::find($id);

                    if (!$type->deleteable) {
                        $fail("《{$type->name}》不允许删除");
                        return;
                    }

                    if (Goods::whereIn('type_id', $type->getAllChild()->pluck('id'))->count()) {
                        $fail("分类下有项目无法删除！");
                    }
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '分类不存在！'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'parentid' => $this->input('parentid'),
            'name'     => $this->input('name')
        ];
    }
}
