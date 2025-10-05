<?php

namespace App\Http\Requests\Web;

use App\Models\Goods;
use App\Models\GoodsType;
use Illuminate\Foundation\Http\FormRequest;

class DrugTypeRequest extends FormRequest
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
            'parentid.required' => '父级ID不能为空！',
            'parentid.integer'  => '父级ID必须是整数！',
            'parentid.exists'   => '父级分类不存在！',
            'name.required'     => '分类名称不能为空！',
            'name.string'       => '分类名称必须是字符串！',
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
            'id.integer'    => '分类ID必须是整数！',
            'id.exists'     => '分类不存在！',
            'name.required' => '分类名称不能为空！',
            'name.string'   => '分类名称必须是字符串！',
            'name.max'      => '分类名称不能超过255个字符！'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:goods_type',
                function ($attribute, $id, $fail) {
                    $type = GoodsType::query()->find($id);

                    if (!$type->deleteable) {
                        $fail("《{$type->name}》不允许删除");
                        return;
                    }

                    if (Goods::query()->whereIn('type_id', $type->getAllChild()->pluck('id'))->count()) {
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
            'id.integer'  => '分类ID必须是整数！',
            'id.exists'   => '分类不存在！'
        ];
    }

    public function formData(): array
    {
        if (request()->route()->getActionMethod() === 'create') {
            return [
                'parentid'   => $this->input('parentid'),
                'name'       => $this->input('name'),
                'type'       => 'drug',
                'editable'   => 1,
                'deleteable' => 1
            ];
        }

        return [
            'name' => $this->input('name')
        ];
    }
}
