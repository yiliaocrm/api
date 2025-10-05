<?php

namespace App\Http\Requests\Web;

use App\Models\PurchaseDetail;
use App\Models\InventoryDetail;
use Illuminate\Foundation\Http\FormRequest;

class ManufacturerRequest extends FormRequest
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
            'info', 'enable', 'disable' => $this->getInfoRules(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:manufacturer'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:manufacturer',
            'name' => 'required|unique:manufacturer,name,' . $this->input('id')
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:manufacturer',
                function ($attribute, $value, $fail) {
                    if (PurchaseDetail::query()->where('manufacturer_id', $value)->first()) {
                        $fail('【采购明细表】已经使用,无法删除!');
                        return;
                    }

                    if (InventoryDetail::query()->where('manufacturer_id', $value)->first()) {
                        $fail('【库存明细表】已经使用,无法删除!');
                    }
                }
            ]
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:manufacturer'
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            'info', 'enable', 'disable' => $this->getInfoMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '生产厂家名称不能为空！',
            'name.unique'   => '生产厂家已存在！'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数！',
            'id.exists'     => '没有找到生产厂家信息',
            'name.required' => '生产厂家名称不能为空！',
            'name.unique'   => '生产厂家已存在！'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到生产厂家信息'
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到生产厂家信息'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'       => $this->input('name'),
            'short_name' => $this->input('short_name'),
            'remark'     => $this->input('remark')
        ];
    }
}
