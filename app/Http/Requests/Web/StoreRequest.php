<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name'           => 'required|unique:stores,name',
            'short_name'     => 'required|unique:stores,short_name',
            'phone'          => 'required|string|max:50',
            'address'        => 'required|string|max:255',
            'business_start' => 'required|string',
            'business_end'   => 'required|string',
            'remark'         => 'nullable|string|max:255',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'latitude'       => 'nullable|numeric|between:-90,90',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required'           => '门店名称不能为空！',
            'name.unique'             => '门店名称已存在！',
            'short_name.required'     => '门店简称不能为空！',
            'short_name.unique'       => '门店简称已存在！',
            'phone.required'          => '联系电话不能为空！',
            'phone.string'            => '联系电话必须为字符串！',
            'phone.max'               => '联系电话不能超过50个字符！',
            'address.required'        => '详细地址不能为空！',
            'address.string'          => '详细地址必须为字符串！',
            'address.max'             => '详细地址不能超过255个字符！',
            'business_start.required' => '营业时间开始不能为空！',
            'business_start.string'   => '营业时间开始必须为字符串！',
            'business_end.required'   => '营业时间结束不能为空！',
            'business_end.string'     => '营业时间结束必须为字符串！',
            'remark.string'           => '门店简介必须为字符串！',
            'remark.max'              => '门店简介不能超过255个字符！',
            'longitude.numeric'       => '经度必须为数字！',
            'longitude.between'       => '经度必须在-180到180之间！',
            'latitude.numeric'        => '纬度必须为数字！',
            'latitude.between'        => '纬度必须在-90到90之间！'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'             => 'required|integer|exists:stores',
            'name'           => 'required|string|unique:stores,name,' . request('id'),
            'short_name'     => 'required|string|unique:stores,short_name,' . request('id'),
            'phone'          => 'required|string|max:50',
            'address'        => 'required|string|max:255',
            'business_start' => 'required|string',
            'business_end'   => 'required|string',
            'remark'         => 'nullable|string|max:255',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'latitude'       => 'nullable|numeric|between:-90,90',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'             => '缺少id参数！',
            'id.integer'              => 'id参数必须为整数！',
            'id.exists'               => '没有找到门店',
            'name.required'           => '门店名称不能为空！',
            'name.string'             => '门店名称必须为字符串！',
            'name.unique'             => '门店名称已存在！',
            'short_name.required'     => '门店简称不能为空！',
            'short_name.string'       => '门店简称必须为字符串！',
            'short_name.unique'       => '门店简称已存在！',
            'phone.required'          => '联系电话不能为空！',
            'phone.string'            => '联系电话必须为字符串！',
            'phone.max'               => '联系电话不能超过50个字符！',
            'address.required'        => '详细地址不能为空！',
            'address.string'          => '详细地址必须为字符串！',
            'address.max'             => '详细地址不能超过255个字符！',
            'business_start.required' => '营业时间开始不能为空！',
            'business_start.string'   => '营业时间开始必须为字符串！',
            'business_end.required'   => '营业时间结束不能为空！',
            'business_end.string'     => '营业时间结束必须为字符串！',
            'remark.string'           => '门店简介必须为字符串！',
            'remark.max'              => '门店简介不能超过255个字符！',
            'longitude.numeric'       => '经度必须为数字！',
            'longitude.between'       => '经度必须在-180到180之间！',
            'latitude.numeric'        => '纬度必须为数字！',
            'latitude.between'        => '纬度必须在-90到90之间！'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:stores,id',
                'not_in:1',
                // 后续加入删除逻辑判断
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.integer'  => 'id参数必须为整数！',
            'id.exists'   => '没有找到门店',
            'id.not_in'   => '不能删除系统默认门店'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'           => $this->input('name'),
            'short_name'     => $this->input('short_name'),
            'phone'          => $this->input('phone'),
            'address'        => $this->input('address'),
            'business_start' => $this->input('business_start'),
            'business_end'   => $this->input('business_end'),
            'remark'         => $this->input('remark'),
            'longitude'      => $this->input('longitude'),
            'latitude'       => $this->input('latitude')
        ];
    }
}
