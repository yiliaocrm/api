<?php

namespace App\Http\Requests\ProductPackageType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'id'   => 'required|exists:product_package_type',
            'name' => 'required'
        ];
    }

    public function formData(): array
    {
        return [
            'name' => $this->input('name')
        ];
    }
}
