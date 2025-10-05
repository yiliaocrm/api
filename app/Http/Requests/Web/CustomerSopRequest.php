<?php

namespace App\Http\Requests\Web;

use App\Models\CustomerSop;
use App\Models\CustomerSopCategory;
use Illuminate\Foundation\Http\FormRequest;

class CustomerSopRequest extends FormRequest
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
            'index' => $this->getIndexRules(),
            'addCategory' => $this->getAddCategoryRules(),
            'swapCategory' => $this->getSwapCategoryRules(),
            'updateCategory' => $this->getUpdateCategoryRules(),
            'removeCategory' => $this->getRemoveCategoryRules(),
            'templateList' => $this->getTemplateListRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'addCategory' => $this->getAddCategoryMessages(),
            'swapCategory' => $this->getSwapCategoryMessages(),
            'updateCategory' => $this->getUpdateCategoryMessages(),
            'removeCategory' => $this->getRemoveCategoryMessages(),
            'templateList' => $this->getTemplateListMessages(),
            default => []
        };
    }

    private function getAddCategoryRules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    private function getAddCategoryMessages(): array
    {
        return [
            'name.required' => '分类名称不能为空',
            'name.string'   => '分类名称必须是字符串',
            'name.max'      => '分类名称不能超过255个字符',
        ];
    }

    private function getUpdateCategoryRules(): array
    {
        return [
            'id'   => 'required|integer|exists:customer_sop_categories,id',
            'name' => 'required|string|max:255',
        ];
    }

    private function getUpdateCategoryMessages(): array
    {
        return [
            'id.required'   => '分类ID不能为空',
            'id.integer'    => '分类ID必须是整数',
            'id.exists'     => '分类ID不存在',
            'name.required' => '分类名称不能为空',
            'name.string'   => '分类名称必须是字符串',
            'name.max'      => '分类名称不能超过255个字符',
        ];
    }

    private function getRemoveCategoryRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:customer_sop_categories,id',
                // 判断是否有旅程使用该分类
                function ($attribute, $value, $fail) {
                    if (CustomerSop::query()->where('category_id', $value)->exists()) {
                        $fail('该分类下有旅程，不能删除');
                        return;
                    }
                },
            ],
        ];
    }

    private function getRemoveCategoryMessages(): array
    {
        return [
            'id.required' => '分类ID不能为空',
            'id.integer'  => '分类ID必须是整数',
            'id.exists'   => '分类ID不存在',
            'id.custom'   => '该分类下有旅程，不能删除',
        ];
    }

    private function getSwapCategoryRules(): array
    {
        return [
            'id1' => 'required|integer|exists:customer_sop_categories,id',
            'id2' => 'required|integer|exists:customer_sop_categories,id',
        ];
    }

    private function getSwapCategoryMessages(): array
    {
        return [
            'id1.required' => '第一个分类ID不能为空',
            'id1.integer'  => '第一个分类ID必须是整数',
            'id1.exists'   => '第一个分类ID不存在',
            'id2.required' => '第二个分类ID不能为空',
            'id2.integer'  => '第二个分类ID必须是整数',
            'id2.exists'   => '第二个分类ID不存在',
        ];
    }

    private function getIndexRules(): array
    {
        return [
            'name'        => 'nullable|string|max:255',
            'category_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value && !CustomerSopCategory::query()->where('id', $value)->exists()) {
                        $fail('分类ID不存在');
                    }
                },
            ],
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'name.string'         => '旅程名称必须是字符串',
            'name.max'            => '旅程名称不能超过255个字符',
            'category_id.integer' => '分类ID必须是整数',
            'category_id.exists'  => '分类ID不存在',
        ];
    }

    private function getTemplateListRules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:customer_sop_template_categories,id',
        ];
    }

    private function getTemplateListMessages(): array
    {
        return [
            'category_id.integer' => '模板分类ID必须是整数',
            'category_id.exists'  => '模板分类ID不存在',
        ];
    }
}
