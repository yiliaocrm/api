<?php

namespace App\Http\Requests\FollowupRole;

use App\Models\FollowupRole;
use App\Models\FollowupTemplateDetail;
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
            'id' => [
                'required',
                function ($attribute, $id, $fail) {
                    $role = FollowupRole::query()->find($id);
                    if (!$role) {
                        return $fail('没有找到数据!');
                    }
                    $count = FollowupTemplateDetail::query()->where('followup_role', $role->value)->count();
                    if ($count) {
                        return $fail('[回访模板]中已使用!');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'id参数不能为空!'
        ];
    }
}
