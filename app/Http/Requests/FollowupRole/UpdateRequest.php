<?php

namespace App\Http\Requests\FollowupRole;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'id'    => 'required|exists:followup_roles',
            'name'  => 'required|unique:followup_roles,name,' . $this->input('id') . ',id',
            'value' => 'required|unique:followup_roles,value,' . $this->input('id') . ',id'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'    => 'id不能为空!',
            'id.exists'      => 'id不存在!',
            'name.required'  => '[橘色名称]不能为空!',
            'name.unique'    => '[角色名称]已存在!',
            'value.required' => '[角色参数]不能为空!',
            'value.unique'   => '[角色参数]已存在!',
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
