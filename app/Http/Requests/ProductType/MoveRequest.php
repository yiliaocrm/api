<?php

namespace App\Http\Requests\ProductType;

use App\Models\ProductType;
use Illuminate\Foundation\Http\FormRequest;

class MoveRequest extends FormRequest
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
            'id'       => 'required|exists:product_type|not_in:1',
            'parentid' => [
                'required',
                function ($attribute, $parentid, $fail) {
                    $parent = ProductType::query()->find($parentid);
                    if (!$parent) {
                        return $fail('没有找到父节点');
                    }
                    $node = ProductType::query()->find($this->input('id'));
                    if ($node->parentid == $parentid) {
                        return $fail('父节点没有改变,无法移动!');
                    }
                    $allNode = $node->getAllChild()->pluck('id')->toArray();
                    if (in_array($parentid, $allNode)) {
                        return $fail('不能移动到子节点下');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'       => '缺少[id]参数!',
            'id.exists'         => '没有找到需要移动的数据!',
            'id.not_in'         => '不能移动根节点',
            'parentid.required' => '缺少[parentid]参数!'
        ];
    }
}
