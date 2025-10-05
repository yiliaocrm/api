<?php

namespace App\Http\Requests\Print;

use App\Models\PrintTemplate;
use App\Models\DepartmentPicking;
use Illuminate\Foundation\Http\FormRequest;

class DepartmentPickingRequest extends FormRequest
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
            'id' => [
                'required',
                'exists:department_picking',
                function ($attribute, $value, $fail) {
                    $departmentPicking = $this->getDepartmentPicking();
                    if ($departmentPicking->status == 1) {
                        $fail('草稿状态无法打印');
                    }
                    if (!$this->getPrintTemplate()) {
                        $fail('默认打印模板不存在');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '领料单ID不能为空',
            'id.exists'   => '领料单ID不存在',
        ];
    }

    public function getDepartmentPicking()
    {
        return DepartmentPicking::query()->find(
            $this->input('id')
        );
    }

    public function getPrintTemplate()
    {
        return PrintTemplate::query()
            ->where('type', 'department_picking')
            ->where('default', 1)
            ->first();
    }
}
