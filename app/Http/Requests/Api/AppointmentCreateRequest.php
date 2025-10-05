<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentCreateRequest extends FormRequest
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
        $rules = [
            'customer_id'   => 'required|exists:customer,id',
            'type'          => 'required|in:coming,treatment,operation',
            'date'          => 'required|date_format:Y-m-d',
            'start'         => 'required|date_format:Y-m-d H:i:s',
            'end'           => 'required|date_format:Y-m-d H:i:s',
            'department_id' => 'required|exists:department,id',
            'doctor_id'     => 'required|numeric',
            'consultant_id' => 'required|numeric',
            'technician_id' => 'required|numeric',
            'items'         => 'required|array|exists:item,id',
            'room_id'       => 'required|numeric'
        ];

        // 手术预约
        if ($this->input('type') === 'operation') {
            $rules['anaesthesia'] = 'required|in:regional,general';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'customer_id.exists'   => '没有找到顾客信息',
            'type.required'        => '[预约类型]不能为空!',
            'type.in'              => '[预约类型]错误!',
            'date.required'        => '[预约日期]不能为空!',
            'anaesthesia.required' => '[麻醉类型]不能为空!',
            'anaesthesia.in'       => '[麻醉类型]错误!',
            'room_id.required'     => '[预约诊室]不能为空!'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        $items      = $this->input('items');
        $items_name = [];

        foreach ($items as $item) {
            $items_name[] = get_item_name($item);
        }

        $data = [
            'type'           => $this->input('type'),
            'customer_id'    => $this->input('customer_id'),
            'date'           => $this->input('date'),
            'start'          => $this->input('start'),
            'end'            => $this->input('end'),
            'duration'       => $this->input('duration'),
            'status'         => 1,
            'items'          => $items,
            'items_name'     => implode('、', $items_name),
            'department_id'  => $this->input('department_id'),
            'doctor_id'      => $this->input('doctor_id'),
            'consultant_id'  => $this->input('consultant_id'),
            'technician_id'  => $this->input('technician_id'),
            'room_id'        => $this->input('room_id'),
            'create_user_id' => user()->id,
            'remark'         => $this->input('remark')
        ];

        // 手术预约
        if ($this->input('type') === 'operation') {
            $data['anaesthesia'] = $this->input('anaesthesia');
        }

        return $data;
    }
}
