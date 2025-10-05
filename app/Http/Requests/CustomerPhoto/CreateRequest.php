<?php

namespace App\Http\Requests\CustomerPhoto;

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
            'customer_id' => 'required|exists:customer,id',
            'title'       => 'required',
            'flag'        => 'required',
        ];
    }

    public function formData(): array
    {
        return [
            'customer_id'    => $this->input('customer_id'),
            'title'          => $this->input('title'),
            'flag'           => $this->input('flag'),
            'remark'         => $this->input('remark'),
            'create_user_id' => user()->id,
        ];
    }
}
