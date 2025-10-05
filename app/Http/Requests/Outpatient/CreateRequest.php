<?php

namespace App\Http\Requests\Outpatient;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
            'customer_id'                  => 'required|exists:customer,id',
            'form'                         => 'required|array',
            'form.type'                    => 'required',
            'form.doctor'                  => 'required|exists:users,id',
            'form.department_id'           => 'required|exists:department,id',
            'form.medium_id'               => 'required|numeric|min:2|exists:medium,id',
            'form.items'                   => 'required|array|exists:item,id',
            'emr'                          => 'required|array',
            'emr.illness_date'             => 'required|date_format:Y-m-d',
            'emr.chief_complaint'          => 'required',
            'emr.diagnosis'                => 'required|array',
            'prescriptions'                => 'nullable|array',
            'prescriptions.*.type'         => 'integer|in:1,2,3,4,5,6',
            'prescriptions.*.warehouse_id' => 'required|exists:warehouse,id',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'         => '[customer_id]不能为空!',
            'customer_id.exists'           => '[顾客信息]不存在!',
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
            'customer_id'   => $this->input('customer_id'),
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
            'remark'        => $this->input('form.remark')
        ];
    }

    public function emrData(): array
    {
        return [
            'customer_id'     => $this->input('customer_id'),
            'illness_date'    => $this->input('emr.illness_date'),
            'chief_complaint' => $this->input('emr.chief_complaint'),
            'present_history' => $this->input('emr.present_history'),
            'past_history'    => $this->input('emr.past_history'),
            'diagnosis'       => $this->input('emr.diagnosis'),
            'user_id'         => user()->id,
        ];
    }

    /**
     * 处方主表数据和明细表数据
     * @param $attributes
     * @return array
     */
    public function prescriptionsData($attributes): array
    {
        $data          = [];
        $prescriptions = $this->input('prescriptions');

        foreach ($prescriptions as $prescription) {
            $details = $prescription['details'];
            $detail  = [];

            foreach ($details as $d) {
                $detail[] = [
                    'reception_id'      => $attributes['reception_id'],
                    'customer_goods_id' => $d['customer_goods_id'],
                    'goods_id'          => $d['goods_id'],
                    'goods_name'        => $d['goods_name'],
                    'package_id'        => $d['package_id'],
                    'package_name'      => $d['package_name'],
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

            $data[] = [
                'prescription' => array_merge($attributes, [
                    'department_id' => $this->input('form.department_id'),
                    'amount'        => collect($detail)->sum('amount'),
                    'user_id'       => user()->id,
                    'status'        => 1,
                ]),
                'detail'       => $detail
            ];
        }

        return $data;
    }
}
