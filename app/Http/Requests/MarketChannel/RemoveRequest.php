<?php

namespace App\Http\Requests\MarketChannel;

use App\Models\Medium;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
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
        return [
            'id' => [
                'required',
                'exists:medium,id',
                function ($attribute, $value, $fail) {
                    if (Medium::query()->where('parentid', $value)->count()) {
                        $fail('该渠道下存在子渠道，无法删除');
                    }

                    if ($value < 10) {
                        $fail('系统数据，不允许删除！');
                    }

                    if (Customer::query()->whereIn('medium_id', Medium::query()->find($value)->getAllChild()->pluck('id'))->count('id')) {
                        $fail('【顾客表】已经使用了该数据，无法直接删除！');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '渠道ID不能为空',
            'id.exists'   => '渠道不存在',
        ];
    }
}
