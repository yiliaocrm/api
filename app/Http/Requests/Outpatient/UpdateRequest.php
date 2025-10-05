<?php

namespace App\Http\Requests\Outpatient;

use App\Models\OutpatientPrescription;
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
            'id'                  => 'required|exists:reception',
            'form'                => 'required|array',
            'form.type'           => 'required',
            'form.doctor'         => 'required|exists:users,id',
            'form.department_id'  => 'required|exists:department,id',
            'form.medium_id'      => 'required|numeric|min:2|exists:medium,id',
            'form.items'          => 'required|array|exists:item,id',
            'emr'                 => 'required|array',
            'emr.illness_date'    => 'required|date_format:Y-m-d',
            'emr.chief_complaint' => 'required',
            'emr.diagnosis'       => 'required|array',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'                  => 'id不能为空!',
            'id.exists'                    => '[接诊记录]没有找到!',
            'form.type'                    => '[接诊状态]不能为空!',
            'form.doctor.required'         => '[接诊医生]不能为空!',
            'form.doctor.exists'           => '[接诊医生]不存在!',
            'form.department_id.required'  => '[就诊科室]不能为空!',
            'form.department_id.exists'    => '[就诊科室]不存在!',
            'form.medium_id.required'      => '[媒介来源]不能为空!',
            'form.medium_id.min'           => '[媒介来源]错误!',
            'form.medium_id.exists'        => '[媒介来源]不存在!',
            'form.items.required'          => '[咨询项目]不能为空!',
            'form.items.exists'            => '[咨询项目]错误!',
            'emr.illness_date.required'    => '[发病时间]不能为空!',
            'emr.illness_date.date_format' => '[发病时间]格式错误!',
            'emr.chief_complaint.required' => '[患者主诉]不能为空!',
            'emr.diagnosis.required'       => '[初步诊断]不能为空!',
        ];
    }

    public function formData(): array
    {
        return [
            'department_id' => $this->input('form.department_id'),
            'items'         => $this->input('form.items'),
            'type'          => $this->input('form.type'),
            'status'        => 1, // 未成交
            // 'consultant'    => user()->id, // 现场咨询
            'reception'     => user()->id,
            'user_id'       => user()->id,
            'medium_id'     => $this->input('form.medium_id'),
            'doctor'        => $this->input('form.doctor'),
            'receptioned'   => 1,
        ];
    }

    public function emrData($customer_id): array
    {
        return [
            'customer_id'     => $customer_id,
            'illness_date'    => $this->input('emr.illness_date'),
            'chief_complaint' => $this->input('emr.chief_complaint'),
            'present_history' => $this->input('emr.present_history'),
            'past_history'    => $this->input('emr.past_history'),
            'diagnosis'       => $this->input('emr.diagnosis'),
            'user_id'         => user()->id,
        ];
    }

    public function prescriptionsData($attributes): array
    {
        $data          = [];
        $prescriptions = $this->input('prescriptions');

        foreach ($prescriptions as $prescription) {
            if (isset($prescription['id'])) {
                continue;
            }

            $details = $prescription['details'];
            $detail  = [];

            foreach ($details as $d) {
                $detail[] = [
                    'customer_goods_id' => $d['customer_goods_id'], // 使用存药
                    'goods_id'          => $d['goods_id'],
                    'goods_name'        => $d['goods_name'],
                    'specs'             => $d['specs'] ?? null,
                    'number'            => $d['number'],
                    'goods_unit'        => $d['goods_unit'],
                    'price'             => $d['price'],
                    'amount'            => $d['amount'],
                    'group'             => $d['group'] ?? null,
                    'dosage'            => $d['dosage'] ?? null,
                    'dosage_unit'       => $d['dosage_unit'] ?? null,
                    'frequency'         => $d['frequency'] ?? null,
                    'days'              => $d['days'] ?? null,
                    'ways'              => $d['ways'] ?? null,
                ];
            }

            $data[] = new OutpatientPrescription(array_merge($attributes, [
                'department_id' => $this->input('form.department_id'),
                'amount'        => collect($detail)->sum('amount'),
                'detail'        => $detail,
                'user_id'       => user()->id,
                'status'        => 1,
            ]));
        }

        return $data;
    }
}
