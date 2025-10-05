<?php

namespace App\Http\Requests\Web;

use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;

class ReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'reception' => $this->getReceptionRules(),
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
            'reception' => $this->getReceptionMessages(),
            default => []
        };
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|string|exists:reservation,id',
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.string'   => 'id参数必须是字符串类型!',
            'id.exists'   => '没有找到数据!'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'string',
                'exists:reservation,id',
                function ($attribute, $value, $fail) {
                    $reservation = Reservation::find($value);

                    // 不允许删除上门后的[咨询]
                    if ($reservation->reception_id && !parameter('reservation_allow_delete_arrive')) {
                        $fail('【系统设置】上门后无法删除！');
                    }
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.string'   => 'id参数必须是字符串类型!',
            'id.exists'   => '没有找到信息!'
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'customer_id'   => [
                'required',
                'string',
                'exists:customer,id',
                function ($attribute, $value, $fail) {
                    $customer = Customer::query()->find($value);

                    if (!$customer) {
                        $fail('没有找到顾客信息!');
                        return;
                    }

                    if (parameter('reservation_only_create_once') && $customer->reservations->count()) {
                        $fail('[系统设置]顾客只能挂一次!');
                        return;
                    }

                    // 只能由开发员自己登记
                    if (parameter('reservation_only_self_create')) {
                        if ($customer->ascription != 0 || $customer->ascription != user()->id) {
                            $fail('顾客不是您的，没有权限操作!');
                        }
                    }

                }
            ],
            'medium_id'     => 'required|numeric|min:2|exists:medium,id',
            'type'          => 'required|integer|exists:reservation_type,id',
            'date'          => 'required|date_format:Y-m-d',
            'department_id' => 'required|integer|exists:department,id',
            'items'         => [
                'required',
                'array',
                'exists:item,id',
                function ($attribute, $value, $fail) {
                    if (!parameter('reservation_allow_multiple_item') && count($value) > 1) {
                        $fail('系统设置,不允许录入多个咨询项目!');
                    }
                }
            ],
            'remark'        => 'required',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'customer_id.required'   => '[顾客id]不能为空!',
            'customer_id.string'     => '[顾客id]格式错误!',
            'medium_id.required'     => '[媒介来源]不能为空',
            'medium_id.min'          => '[媒介来源]错误!',
            'medium_id.exists'       => '[媒介来源]不存在!',
            'type.required'          => '[受理类型]不能为空!',
            'type.integer'           => '[受理类型]格式错误!',
            'type.exists'            => '[受理类型]不存在!',
            'date.required'          => '[受理日期]不能为空!',
            'date.date_format'       => '[受理日期]格式错误!',
            'department_id.required' => '[咨询科室]不能为空!',
            'department_id.integer'  => '[咨询科室]格式错误!',
            'department_id.exists'   => '[咨询科室]不存在!',
            'items.required'         => '[咨询项目]不能为空!',
            'items.exists'           => '[咨询项目]不存在!',
            'remark.required'        => '[咨询备注]不能为空!',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'            => [
                'required',
                function ($attribute, $value, $fail) {

                    $reservation = Reservation::query()->find($value);

                    if (!$reservation) {
                        $fail('缺少id参数或数据不存在!');
                        return;
                    }

                    if ($reservation->reception_id && !parameter('reservation_allow_update_arrive')) {
                        $fail('[系统设置]顾客上门后无法修改！');
                        return;
                    }

                    if (!user()->hasAnyAccess(['superuser', 'reservation.update'])) {
                        $fail('您没有权限修改咨询信息!');
                    }
                }
            ],
            'medium_id'     => 'required|integer|exists:medium,id',
            'type'          => 'required|integer|exists:reservation_type,id',
            'date'          => 'required|date_format:Y-m-d',
            'time'          => 'nullable|date_format:Y-m-d H:i:s',
            'department_id' => 'required|integer|exists:department,id',
            'items'         => [
                'required',
                'array',
                'exists:item,id',
                function ($attribute, $value, $fail) {
                    if (!parameter('reservation_allow_multiple_item') && count($value) > 1) {
                        $fail('系统设置,不允许录入多个咨询项目!');
                    }
                }
            ],
            'remark'        => 'required',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'            => '缺少id参数!',
            'medium_id.required'     => '[媒介来源]不能为空',
            'medium_id.integer'      => '[媒介来源]格式错误',
            'medium_id.exists'       => '[媒介来源]不存在!',
            'type.required'          => '[受理类型]不能为空!',
            'type.integer'           => '[受理类型]格式错误!',
            'type.exists'            => '[受理类型]不存在!',
            'date.required'          => '[受理日期]不能为空!',
            'date.date_format'       => '[受理日期]格式错误!',
            'department_id.required' => '[咨询科室]不能为空!',
            'department_id.integer'  => '[咨询科室]格式错误!',
            'department_id.exists'   => '[咨询科室]不存在!',
            'items.required'         => '[咨询项目]不能为空!',
            'items.exists'           => '[咨询项目]不存在!',
            'remark.required'        => '[咨询备注]不能为空!',
        ];
    }

    private function getReceptionRules(): array
    {
        return [
            'start' => 'nullable|date',
            'end'   => 'nullable|date|after_or_equal:start',
        ];
    }

    private function getReceptionMessages(): array
    {
        return [
            'start.date'         => '[咨询日期-开始时间]格式错误!',
            'end.date'           => '[咨询日期-结束时间]格式错误!',
            'end.after_or_equal' => '[咨询日期-结束时间]必须大于[咨询日期-开始时间]!',
        ];
    }

    public function formData(): array
    {
        $data = [
            'status'        => 1, // 未上门
            'medium_id'     => $this->input('medium_id'),
            'type'          => $this->input('type'),
            'date'          => $this->input('date'),
            'department_id' => $this->input('department_id'),
            'items'         => $this->input('items'),
            'remark'        => $this->input('remark'),
            'customer_id'   => $this->input('customer_id'),
            'ascription'    => user()->id,
            'user_id'       => user()->id,
            'time'          => $this->input('time'),
        ];

        if (request()->route()->getActionMethod() === 'update') {
            unset($data['status'], $data['customer_id'], $data['ascription'], $data['user_id']);
        }

        return $data;
    }
}
