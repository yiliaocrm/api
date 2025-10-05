<?php

namespace App\Http\Requests\Web;

use App\Models\Customer;
use App\Models\Reception;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ReceptionRequest extends FormRequest
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

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'info' => $this->getInfoRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            'dispatchDoctor', 'dispatchConsultant' => $this->getDispatchDoctorRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'info' => $this->getInfoMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            'dispatchDoctor', 'dispatchConsultant' => $this->getDispatchDoctorMessages(),
            default => []
        };
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:reception'
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '数据不存在!',
        ];
    }

    private function getDispatchDoctorRules(): array
    {
        return [
            'id'  => 'required|exists:reception',
            'new' => 'required|exists:users,id'
        ];
    }

    private function getDispatchDoctorMessages(): array
    {
        return [
            'id.required'  => '缺少id参数!',
            'id.exists'    => '数据不存在!',
            'new.required' => '缺少new参数!',
            'new.exists'   => '数据不存在!',
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'consultant'  => 'required|integer|exists:users,id',
            'medium_id'   => 'required|integer|exists:medium,id',
            'items'       => [
                'required',
                'array',
                'exists:item,id',
                function ($attribute, $value, $fail) {
                    if (!parameter('consultant_allow_multiple_item') && count($value) > 1) {
                        $fail('系统设置,不允许录入多个咨询项目!');
                    }
                }
            ],
            'reception'   => 'required|integer|exists:users,id',
            'doctor'      => 'nullable|integer|exists:users,id',
            'customer_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $customer = Customer::query()->find($value);
                    if (!$customer) {
                        $fail('顾客信息不存在!');
                        return;
                    }
                    // 现场咨询开启{首诊制}
                    if (parameter('consultant_only_self_create') && $customer->consultant != 0 && $customer->consultant != $this->input('consultant')) {
                        $fail('系统开启{首诊制}现场咨询不匹配无法分诊');
                    }
                },
            ],
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'consultant.exists'  => '[咨询人员]不能为空!',
            'medium_id.required' => '[媒介来源]不能为空!',
            'medium_id.exists'   => '[媒介来源]不存在!',
            'items.required'     => '[咨询项目]不能为空!',
            'items.exists'       => '[咨询项目]不存在!',
            'reception.required' => '[接待人员]不能为空!',
            'reception.exists'   => '[接待人员]不存在!',
            'doctor.exists'      => '[接诊医生]不存在!',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'    => [
                'required',
                'exists:reception',
                function ($attribute, $value, $fail) {
                    $reception = Reception::find($value);

                    if ($reception->receptioned) {
                        $fail('现场咨询接待后无法修改。');
                        return;
                    }

                    if (!user()->hasAnyAccess(['superuser', 'reception.update'])) {
                        $fail('您没有权限修改分诊信息!');
                    }
                }
            ],
            'items' => [
                'required',
                'array',
                'exists:item,id',
                function ($attribute, $value, $fail) {
                    if (!parameter('consultant_allow_multiple_item') && count($value) > 1) {
                        $fail('系统设置,不允许录入多个咨询项目!');
                    }
                }
            ],
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'    => '[id参数]不能为空!',
            'id.exists'      => '没有找到数据!',
            'items.required' => '[咨询项目]不能为空!',
            'items.exists'   => '[咨询项目]不存在!',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $reception = Reception::find($value);
                    if (!$reception) {
                        $fail('数据不存在!');
                        return;
                    }
                    if ($reception->receptioned) {
                        $fail('现场咨询接待后无法删除。');
                    }
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        $data = [
            'reception'     => $this->input('reception'),
            'department_id' => $this->input('department_id'),
            'type'          => $this->input('type'),
            'ek_user'       => $this->input('ek_user'),
            'medium_id'     => $this->input('medium_id'),
            'items'         => $this->input('items'),
            'remark'        => $this->input('remark')
        ];

        // 新增
        if (request()->route()->getActionMethod() === 'create') {
            $data['status']      = 1;   // 未成交
            $data['doctor']      = $this->input('doctor');
            $data['user_id']     = user()->id;
            $data['consultant']  = $this->input('consultant');
            $data['customer_id'] = $this->input('customer_id');
            $data['receptioned'] = 0;
        }

        return $data;
    }
}
