<?php

namespace App\Http\Requests\InventoryTransfer;

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
    public function rules(): array
    {
        return [
            'id' => 'required|exists:inventory_transfer,id,status,1'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '调拨单不存在或者已审核',
        ];
    }
}
