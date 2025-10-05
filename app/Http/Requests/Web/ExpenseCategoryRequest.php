<?php

namespace App\Http\Requests\Web;

use App\Models\Goods;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseCategoryRequest extends FormRequest
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

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:expense_category'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:expense_category',
            'name' => [
                'required',
                'string',
                Rule::unique('expense_category')->ignore($this->input('id'))
            ]
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:expense_category',
                function ($attribute, $value, $fail) {
                    if ($product = Product::where('expense_category_id', $value)->first()) {
                        $fail("项目《{$product->name}》已经使用,无法删除");
                        return;
                    }

                    if ($goods = Goods::where('expense_category_id', $value)->first()) {
                        $fail("商品《{$goods->name}》已经使用,无法删除");
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
            'name.required' => '缺少name参数',
            'name.unique'   => "《{$this->input('name')}》重复"
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数',
            'id.exists'     => '没有找到数据',
            'name.required' => '缺少name参数',
            'name.unique'   => "《{$this->input('name')}》重复"
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '没有找到数据',
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name' => $this->input('name')
        ];
    }
}
