<?php

namespace App\Http\Requests\Api;

use Carbon\Carbon;
use App\Models\Customer;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
        return match (request()->route()->getActionMethod()) {
            default => [],
            'profile', 'followup', 'reservation', 'photo' => [
                'customer_id' => 'required|exists:customer,id',
            ],
            'query' => [
                'keyword'  => 'required',
                'category' => 'required|in:keyword,phone'
            ],
            'info' => [
                'id' => 'required|exists:customer,id',
            ],
            'create' => [
                'name'        => 'required',
                'sex'         => 'required|in:1,2',
                'phone'       => [
                    'required',
                    'array',
                    new PhoneRule()
                ],
                'idcard'      => 'nullable|unique:customer,idcard',
                'file_number' => 'nullable|unique:customer,file_number',
                'sfz'         => 'nullable|string|max:30',
                'address_id'  => 'required|integer|exists:address,id',
                'medium_id'   => 'required|integer|exists:medium,id',
                'job_id'      => 'nullable|exists:customer_job,id',
                'age'         => 'nullable|integer|between:1,199',
                'birthday'    => 'nullable|date_format:Y-m-d',
                'qq'          => 'nullable|string',
                'wechat'      => 'nullable|string',
                'marital'     => 'nullable|in:1,2,3',
                'economic_id' => 'nullable|exists:customer_economic,id',
                'tags'        => 'nullable|array|exists:tags,id'
            ]
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'profile', 'followup', 'reservation', 'photo' => [
                'customer_id.required' => '顾客ID不能为空',
                'customer_id.exists'   => '顾客ID不存在',
            ],
            'query' => [
                'keyword.required'  => '关键词不能为空!',
                'category.required' => '搜索类型不能为空!',
                'category.in'       => '搜索类型错误'
            ],
            'info' => [
                'id.required' => '顾客ID不能为空',
                'id.exists'   => '顾客ID不存在',
            ],
            'create' => [
                'name.required'        => '[顾客姓名]不能为空!',
                'sex.required'         => '[顾客性别]不能为空!',
                'sex.in'               => '[顾客性别]数据错误!',
                'idcard.unique'        => '[顾客卡号]重复!',
                'file_number.unique'   => '[档案编号]重复!',
                'sfz.max'              => '[身份证号]不能超过30位!',
                'address_id.required'  => '[联系地址]不能为空!',
                'address_id.exists'    => '[联系地址]数据错误!',
                'medium_id.required'   => '[首次来源]不能为空!',
                'medium_id.exists'     => '[首次来源]不存在!',
                'job_id.exists'        => '[职业信息]不存在!',
                'age.integer'          => '[顾客年龄]数据错误!',
                'age.between'          => '[顾客年龄]只能在1-199之间!',
                'birthday.date_format' => '[顾客生日]格式错误!',
                'qq.string'            => '[联系QQ]格式错误!',
                'wechat.string'        => '[微信号码]格式错误!',
                'marital.in'           => '[婚姻状况]数据错误!',
                'economic_id.exists'   => '[经济能力]不存在',
                'tags.exists'          => '[顾客标签]数据错误!',
            ]
        };
    }

    /**
     * 创建顾客信息
     * @return array
     */
    public function createData(): array
    {
        $data = [
            'name'        => $this->input('name'),
            'sex'         => $this->input('sex'),
            'phone'       => $this->input('phone'),
            'idcard'      => $this->input('idcard'),
            'file_number' => $this->input('file_number'),
            'sfz'         => $this->input('sfz'),
            'address_id'  => $this->input('address_id'),
            'medium_id'   => $this->input('medium_id'),
            'job_id'      => $this->input('job_id'),
            'age'         => $this->input('age'),
            'birthday'    => $this->input('birthday'),
            'qq'          => $this->input('qq'),
            'wechat'      => $this->input('wechat'),
            'marital'     => $this->input('marital'),
            'economic_id' => $this->input('economic_id'),
            'tags'        => $this->input('tags', []),
            'remark'      => $this->input('remark'),
            'user_id'     => user()->id,    // 创建人员
            'ascription'  => user()->id,    // 开发人员
        ];

        // 解析年纪
        if ($data['birthday'] && !$data['age']) {
            $data['age'] = Carbon::parse($data['birthday'])->age;
        }

        // 自动生成卡号
        if (!$data['idcard']) {
            $data['idcard'] = date('Ymd') . str_pad((Customer::query()->today()->count() + 1), 4, '0', STR_PAD_LEFT);
        }

        return $data;
    }
}
