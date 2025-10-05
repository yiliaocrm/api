<?php

namespace App\Http\Requests\DiagnosisCategory;

use App\Models\Diagnosis;
use App\Models\DiagnosisCategory;
use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
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
            'id' => [
                'required',
                'exists:diagnosis_category',
                function ($attribute, $value, $fail) {
                    $ids   = DiagnosisCategory::find($value)->getAllChild()->pluck('id');
                    $count = Diagnosis::whereIn('category_id', $ids)->count();
                    if ($count) {
                        $fail('诊断分类下有数据,无法删除!');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '缺少id参数',
        ];
    }
}
