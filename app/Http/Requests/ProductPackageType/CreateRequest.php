<?php

namespace App\Http\Requests\ProductPackageType;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
            'parentid' => 'required|exists:product_package_type,id',
            'name'     => 'required'
        ];
    }

    public function formData(): array
    {
        return [
            'parentid' => $this->input('parentid'),
            'name'     => $this->input('name')
        ];
    }
}
