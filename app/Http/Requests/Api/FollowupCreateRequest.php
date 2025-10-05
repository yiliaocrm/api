<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class FollowupCreateRequest extends FormRequest
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
            'customer_id'   => 'required|exists:customer,id',
            'title'         => 'required',
            'date'          => 'required|date_format:Y-m-d',
            'type'          => 'required|exists:followup_type,id',
            'tool'          => 'nullable|exists:followup_tool,id',
            'followup_user' => 'required|exists:users,id'
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'   => 'customer_id参数不能为空!',
            'customer_id.exists'     => '[顾客信息]没有找到!',
            'title.required'         => '[回访主题]不能为空!',
            'date.required'          => '[提醒日期]不能为空!',
            'date.date_format'       => '[提醒日期]格式错误!',
            'type.required'          => '[回访类型]不能为空!',
            'followup_user.required' => '[提醒人员]不能为空!',
        ];
    }

    public function formData(): array
    {
        return [
            'customer_id'   => $this->input('customer_id'),
            'type'          => $this->input('type'),
            'status'        => $this->input('remark') ? 2 : 1, // 回访状态
            'tool'          => $this->input('tool'),
            'title'         => $this->input('title'),
            'date'          => $this->input('date'),
            'time'          => $this->input('remark') ? date("Y-m-d H:i:s") : null,
            'remark'        => $this->input('remark') ?? null,
            'followup_user' => $this->input('followup_user'),
            'execute_user'  => $this->input('remark') ? user()->id : null,
            'user_id'       => user()->id,
        ];
    }
}
