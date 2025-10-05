<?php

namespace App\Http\Requests\Web;

use App\Models\CustomerProduct;
use Illuminate\Foundation\Http\FormRequest;

class ProductPackageRequest extends FormRequest
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
            'choose' => $this->getChooseRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    private function getChooseRules(): array
    {
        return [
            'type_id' => 'nullable|exists:product_package_type,id'
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'form.title'              => 'required|max:255',
            'form.type_id'            => 'required|exists:product_package_type,id',
            'details'                 => 'required|array',
            'details.*.type'          => 'required|in:goods,product',
            'details.*.times'         => 'required|numeric',
            'details.*.product_id'    => 'nullable|integer|exists:product,id',
            'details.*.goods_id'      => 'nullable|integer|exists:goods,id',
            'details.*.price'         => 'required',
            'details.*.sales_price'   => 'required',
            'details.*.department_id' => 'nullable|exists:department,id',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'form.id'                 => 'required|exists:product_package,id',
            'form.title'              => 'required|max:255',
            'form.type_id'            => 'required|exists:product_package_type,id',
            'details'                 => 'required|array',
            'details.*.type'          => 'required|in:goods,product',
            'details.*.times'         => 'required|numeric',
            'details.*.product_id'    => 'nullable|integer|exists:product,id',
            'details.*.goods_id'      => 'nullable|integer|exists:goods,id',
            'details.*.price'         => 'required',
            'details.*.sales_price'   => 'required',
            'details.*.department_id' => 'nullable|exists:department,id',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:product_package',
                function ($attribute, $value, $fail) {
                    if (CustomerProduct::query()->where('package_id', $value)->first()) {
                        $fail('套餐已经被使用,无法删除!');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'choose' => $this->getChooseMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getChooseMessages(): array
    {
        return [
            'type_id.exists' => '[套餐分类]不存在!'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'form.title.required'   => '[套餐名称]不能为空!',
            'form.title.max'        => '[套餐名称]不能超过255个字!',
            'form.type_id.required' => '[套餐分类]不能为空!',
            'form.type_id.exists'   => '[套餐分类]不存在!'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'form.title.required'   => '[套餐名称]不能为空!',
            'form.title.max'        => '[套餐名称]不能超过255个字!',
            'form.type_id.required' => '[套餐分类]不能为空!',
            'form.type_id.exists'   => '[套餐分类]不存在!'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '没有找到套餐数据!',
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateFormData(),
            'update' => $this->getUpdateFormData(),
            default => []
        };
    }

    private function getCreateFormData(): array
    {
        return [
            'title'     => $this->input('form.title'),
            'type_id'   => $this->input('form.type_id'),
            'splitable' => $this->input('form.splitable', true),
            'editable'  => $this->input('form.editable', true),
            'amount'    => collect($this->input('details'))->sum('sales_price'),
            'user_id'   => user()->id,
            'keyword'   => implode(',', parse_pinyin($this->input('form.title'))),
        ];
    }

    private function getUpdateFormData(): array
    {
        return [
            'title'     => $this->input('form.title'),
            'type_id'   => $this->input('form.type_id'),
            'splitable' => $this->input('form.splitable', true),
            'editable'  => $this->input('form.editable', true),
            'amount'    => collect($this->input('details'))->sum('sales_price'),
            'user_id'   => user()->id,
            'keyword'   => implode(',', parse_pinyin($this->input('form.title'))),
            'disabled'  => $this->input('form.disabled', false)
        ];
    }

    /**
     * 详情数据
     * @return array
     */
    public function detailsData(): array
    {
        $data    = [];
        $details = $this->input('details');

        foreach ($details as $detail) {
            $data[] = [
                'type'          => $detail['type'],
                'product_id'    => $detail['product_id'] ?? null,
                'product_name'  => $detail['product_name'] ?? null,
                'goods_id'      => $detail['goods_id'] ?? null,
                'goods_name'    => $detail['goods_name'] ?? null,
                'times'         => $detail['times'],
                'unit_id'       => $detail['unit_id'] ?? null,
                'specs'         => $detail['specs'] ?? null,
                'price'         => $detail['price'],
                'sales_price'   => $detail['sales_price'],
                'department_id' => $detail['department_id'] ?? null,
                'remark'        => $detail['remark'] ?? null
            ];
        }

        return $data;
    }
}
