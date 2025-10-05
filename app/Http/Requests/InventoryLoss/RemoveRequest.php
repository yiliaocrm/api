<?php

namespace App\Http\Requests\InventoryLoss;

use Illuminate\Validation\Rule;
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
     * @return array
     */
    public function rules()
    {
        return [
            'id' => [
                'required',
                Rule::exists('inventory_losses')->where('status', 1)
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'id不能为空!',
        ];
    }
}
