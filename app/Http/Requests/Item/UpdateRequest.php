<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'   => [
                'required',
                function ($attribute, $value, $fail) {
                    if (parameter('cywebos_enable_item_product_type_sync')) {
                        $fail('系统开启[咨询项目]与[收费项目分类]同步,无法修改[咨询项目]');
                    }
                }
            ],
            'name' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'   => '缺少id参数！',
            'name.required' => '缺少name参数'
        ];
    }

    public function formData(): array
    {
        return [
            'name' => trim($this->input('name'))
        ];
    }
}
