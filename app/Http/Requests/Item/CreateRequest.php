<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
            'parentid' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (parameter('cywebos_enable_item_product_type_sync')) {
                        $fail('系统开启[咨询项目]与[收费项目分类]同步,无法单独创建[咨询项目]');
                    }
                }
            ],
            'name'     => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'parentid.required' => '缺少parentid参数',
            'name.required'     => '缺少name参数'
        ];
    }

    public function formData(): array
    {
        $data     = [];
        $names    = array_filter(explode("\n", $this->input('name')));
        $parentid = $this->input('parentid');

        foreach ($names as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }
            $data[] = [
                'name'     => $name,
                'parentid' => $parentid
            ];
        }

        return $data;
    }
}
