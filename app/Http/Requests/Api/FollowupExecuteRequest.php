<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class FollowupExecuteRequest extends FormRequest
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
            'id' => 'required|exists:followup'
        ];
    }

    public function messages(): array
    {
        return [

        ];
    }

    public function formData(): array
    {
        return [
            'title'        => $this->input('title'),
            'type'         => $this->input('type'),
            'tool'         => $this->input('tool'),
            'time'         => date("Y-m-d H:i:s"),
            'remark'       => $this->input('remark'),
            'execute_user' => user()->id,
            'status'       => 2,  // 已回访
        ];
    }
}
