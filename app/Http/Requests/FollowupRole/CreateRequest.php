<?php

namespace App\Http\Requests\FollowupRole;

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
            'name'  => 'required|unique:followup_roles',
            'value' => 'required|unique:followup_roles'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => '[回访角色名称]不能为空!',
            'name.unique'    => '[回访角色名称]已经存在!',
            'value.required' => '[回访角色参数]不能为空',
            'value.unique'   => '[回访角色参数]已经存在!'
        ];
    }

    public function formData(): array
    {
        return [
            'name'  => $this->input('name'),
            'value' => $this->input('value')
        ];
    }
}
