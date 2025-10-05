<?php

namespace App\Http\Requests\CustomerLevel;

use App\Models\Customer;
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
                'exists:customer_level',
                function ($attribute, $value, $fail) {
                    $count = Customer::where('level_id', $value)->count();
                    if ($count) {
                        $fail('会员等级已经被使用,无法删除');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到数据!'
        ];
    }
}
