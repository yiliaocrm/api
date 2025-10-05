<?php

namespace App\Http\Requests\Item;

use App\Rules\Item\RemoveRule;
use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => [
                'required',
                'exists:item',
                'not_in:1',
                function ($attribute, $value, $fail) {
                    if (parameter('cywebos_enable_item_product_type_sync')) {
                        $fail('系统开启[咨询项目]与[收费项目分类]同步,无法删除[咨询项目]');
                    }
                },
                new RemoveRule
            ]
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到数据!',
        ];
    }
}
