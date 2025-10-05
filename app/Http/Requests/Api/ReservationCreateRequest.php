<?php

namespace App\Http\Requests\Api;

use App\Models\Customer;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReservationCreateRequest extends FormRequest
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
        $rules = [
            'reservation.department_id' => 'required|exists:department,id',
            'reservation.type'          => 'required|integer|exists:reservation_type,id',
            'reservation.date'          => 'required|date_format:Y-m-d',
            'reservation.time'          => 'nullable|date_format:Y-m-d H:i:s',
            'reservation.items'         => [
                'required',
                'array',
                'exists:item,id',
                function ($attribute, $value, $fail) {
                    if (!parameter('reservation_allow_multiple_item') && count($value) > 1) {
                        $fail('系统设置,不允许录入多个咨询项目!');
                    }
                }
            ],
            'reservation.remark'        => 'required'
        ];

        // 为顾客新增报单记录
        if ($this->input('customer_id')) {
            $rules['customer_id'] = 'required|exists:customer,id';
        } else {
            $rules['customer.name']       = 'required';
            $rules['customer.sex']        = 'required|in:1,2';
            $rules['customer.phone']      = [
                'required',
                'array',
                new PhoneRule()
            ];
            $rules['customer.address_id'] = 'required|integer|exists:address,id';
            $rules['customer.medium_id']  = 'required|integer|exists:medium,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'reservation.department_id.required' => '[咨询科室]不能为空!',
            'reservation.type.required'          => '[受理类型]不能为空!',
            'reservation.type.integer'           => '[受理类型]格式错误!',
            'reservation.type.exists'            => '[受理类型]不存在!',
            'reservation.date.required'          => '[受理日期]不能为空!',
            'reservation.date.date_format'       => '[受理日期]格式错误!',
            'reservation.time.date_format'       => '[预约时间]格式错误!',
            'reservation.items.required'         => '[咨询项目]不能为空!',
            'reservation.items.exists'           => '[咨询项目]不存在!',
            'reservation.remark.required'        => '[咨询备注]不能为空!',
            'customer.name.required'             => '[顾客姓名]不能为空!',
            'customer.sex.required'              => '[顾客性别]不能为空!',
            'customer.sex.in'                    => '[顾客性别]错误!',
            'customer.address_id.required'       => '[联系地址]不能为空!',
            'customer.address_id.exists'         => '[联系地址]数据错误!',
            'customer.medium_id.required'        => '[首次来源]不能为空!',
            'customer.medium_id.exists'          => '[首次来源]不存在!',
        ];
    }

    /**
     * 创建顾客
     * @return array
     */
    public function createCustomerData(): array
    {
        $customer = [
            'name'        => $this->input('customer.name'),
            'sex'         => $this->input('customer.sex'),
            'phone'       => $this->input('customer.phone'),
            'address_id'  => $this->input('customer.address_id'),
            'medium_id'   => $this->input('customer.medium_id'),
            'idcard'      => $this->input('customer.idcard'),
            'file_number' => $this->input('customer.file_number'),
            'qq'          => $this->input('customer.qq'),
            'wechat'      => $this->input('customer.wechat'),
            'sfz'         => $this->input('customer.sfz'),
            'user_id'     => user()->id,    // 创建人员
            'ascription'  => user()->id,    // 开发人员
        ];

        // 自动生成卡号
        if (!$customer['idcard']) {
            $customer['idcard'] = date('Ymd') . str_pad((Customer::today()->count() + 1), 4, '0', STR_PAD_LEFT);
        }

        return $customer;
    }

    public function reservationData(string $customer_id): array
    {
        return [
            'status'        => 1, // 未上门
            'customer_id'   => $customer_id,
            'medium_id'     => $this->input('customer.medium_id'), // 要区分
            'type'          => $this->input('reservation.type'),
            'date'          => $this->input('reservation.date'),
            'time'          => $this->input('reservation.time'),
            'department_id' => $this->input('reservation.department_id'),
            'items'         => $this->input('reservation.items'),
            'remark'        => $this->input('reservation.remark'),
            'ascription'    => user()->id,
            'user_id'       => user()->id
        ];
    }

    /**
     * 更新顾客信息(咨询项目)
     * @param $customer
     * @return array
     */
    public function updateCustomerData($customer): array
    {
        return [
            'items' => $customer->reservations->pluck('items')->collapse()->merge($customer->receptions->pluck('items')->collapse())->unique()->values()
        ];
    }
}
