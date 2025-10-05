<?php

namespace App\Http\Requests\Web;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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

    private function getCreateRules(): array
    {
        return [
            'parentid' => 'nullable|exists:address,id',
            'name'     => 'required'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'parentid.exists' => '没有找到父级数据',
            'name.required'   => '名称不能为空!'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'       => [
                'required',
                'exists:address',
                function ($attribute, $value, $fail) {
                    if ($this->input('parentid') && in_array($this->input('parentid'), Address::query()->find($value)->getAllChild()->pluck('id')->toArray())) {
                        $fail('不能移动到自己的子分类下！');
                    }
                }
            ],
            'parentid' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }
                    $address = Address::query()->find($this->input('id'));
                    $parent  = Address::query()->find($value);
                    if (!$parent) {
                        $fail('父级分类不存在！');
                    }
                    if ($address->parentid == $value) {
                        return;
                    }
                    if (in_array($this->input('id'), $parent->getAllChild()->pluck('id')->toArray())) {
                        $fail('不能移动到自己的子分类下！');
                    }
                }
            ],
            'name'     => 'required'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'     => 'id不能为空!',
            'id.exists'       => '没有找到要更新的数据',
            'parentid.exists' => '没有找到父级数据',
            'name.required'   => '名称不能为空!'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:address',
                function ($attribute, $value, $fail) {
                    if (Customer::query()->whereIn('address_id', Address::find($value)->getAllChild()->pluck('id'))->count('id')) {
                        $fail('【顾客表】已经使用了该数据，无法直接删除！');
                    }
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => 'id不能为空!',
            'id.exists'   => '没有找到要删除的数据'
        ];
    }

    public function formData(): array
    {
        return [
            'parentid' => $this->input('parentid', 0) ?? 0,
            'name'     => $this->input('name')
        ];
    }
}
