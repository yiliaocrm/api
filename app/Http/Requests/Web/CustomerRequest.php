<?php

namespace App\Http\Requests\Web;

use Carbon\Carbon;
use App\Models\Customer;
use App\Rules\PhoneRule;
use App\Rules\Web\SceneRule;
use Illuminate\Validation\Rule;
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
            'index' => $this->getIndexRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'import' => $this->getImportRules(),
            'merge' => $this->getMergeRules(),
            'query' => $this->getQueryRules(),
            'info', 'fill', 'remove', 'profile' => $this->getInfoRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'import' => $this->getImportMessages(),
            'merge' => $this->getMergeMessages(),
            'query' => $this->getQueryMessages(),
            'info', 'fill', 'remove', 'profile' => $this->getInfoMessages(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'filters' => [
                'nullable',
                'array',
                new SceneRule('CustomerIndex')
            ],
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'name'                 => 'required|string|max:250',
            'sex'                  => 'required|numeric|in:1,2',
            'phones'               => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $relationIds = array_column($value, 'relation_id');
                    $counts      = array_count_values($relationIds);
                    if (isset($counts[1]) && $counts[1] > 1) {
                        $fail('[本人]关系电话只能设置一个。');
                    }
                    $phoneNumberMax = parameter('customer_phone_max');
                    if ($phoneNumberMax && count($value) > $phoneNumberMax) {
                        $fail("联系电话最多只能设置{$phoneNumberMax}个。");
                    }
                },
            ],
            'phones.*.phone'       => [
                'required',
                'string',
                new PhoneRule()
            ],
            'phone.*.relation_id'  => 'required|integer|exists:customer_phone_relationships,id',
            'idcard'               => 'nullable|string|max:255|unique:customer,idcard',
            'file_number'          => 'nullable|string|max:255|unique:customer,file_number',
            'sfz'                  => 'nullable|string|max:30',
            'address_id'           => 'required|integer|exists:address,id',
            'medium_id'            => 'required|integer|exists:medium,id',
            'referrer_user_id'     => [
                'required_if:medium_id,2',
                'prohibited_if:medium_id,3', // 当 medium_id 为 3 时，禁止该字段
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'referrer_customer_id' => [
                'required_if:medium_id,3',
                'prohibited_if:medium_id,2', // 当 medium_id 为 2 时，禁止该字段
                'nullable',
                'string',
                'exists:customer,id'
            ],
            'job_id'               => 'nullable|integer|exists:customer_job,id',
            'age'                  => 'nullable|integer|between:1,199',
            'birthday'             => 'nullable|date_format:Y-m-d',
            'qq'                   => 'nullable|string',
            'wechat'               => 'nullable|string',
            'marital'              => 'nullable|in:1,2,3',
            'economic_id'          => 'nullable|exists:customer_economic,id',
            'tags'                 => 'nullable|array|exists:tags,id'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required'                      => '[顾客姓名]不能为空!',
            'name.string'                        => '[顾客姓名]格式错误!',
            'name.max'                           => '[顾客姓名]不能超过250个字符!',
            'sex.required'                       => '[顾客性别]不能为空!',
            'sex.numeric'                        => '[顾客性别]数据类型错误!',
            'sex.in'                             => '[顾客性别]数据错误!',
            'phones.required'                    => '[联系电话]不能为空!',
            'phones.array'                       => '[联系电话]格式错误!',
            'phones.*.phone.required'            => '[联系电话]不能为空!',
            'phones.*.phone.string'              => '[联系电话]格式错误!',
            'phones.*.phone.distinct'            => '[联系电话]不能重复!',
            'phones.*.relation_id.required'      => '[联系电话关系]不能为空!',
            'phones.*.relation_id.integer'       => '[联系电话关系]数据类型错误!',
            'phones.*.relation_id.exists'        => '[联系电话关系]不存在!',
            'idcard.required'                    => '[顾客卡号]不能为空!',
            'idcard.unique'                      => '[顾客卡号]重复!',
            'idcard.max'                         => '[顾客卡号]不能超过255个字符!',
            'idcard.string'                      => '[顾客卡号]格式错误!',
            'file_number.string'                 => '[档案编号]格式错误!',
            'file_number.unique'                 => '[档案编号]重复!',
            'file_number.max'                    => '[档案编号]不能超过255个字符!',
            'sfz.string'                         => '[身份证号]格式错误!',
            'sfz.max'                            => '[身份证号]不能超过30位!',
            'address_id.required'                => '[联系地址]不能为空!',
            'address_id.exists'                  => '[联系地址]数据错误!',
            'medium_id.required'                 => '[首次来源]不能为空!',
            'medium_id.integer'                  => '[首次来源]数据类型错误!',
            'medium_id.exists'                   => '[首次来源]不存在!',
            'referrer_user_id.required_if'       => '[推荐员工]不能为空!',
            'referrer_user_id.prohibited_if'     => '[推荐员工]必须为空!',
            'referrer_user_id.integer'           => '[推荐员工]数据类型错误!',
            'referrer_user_id.exists'            => '[推荐员工]不存在!',
            'referrer_customer_id.required_if'   => '[推荐顾客]不能为空!',
            'referrer_customer_id.prohibited_if' => '[推荐顾客]必须为空!',
            'referrer_customer_id.string'        => '[推荐顾客]数据类型错误!',
            'referrer_customer_id.exists'        => '[推荐顾客]不存在!',
            'job_id.integer'                     => '[职业信息]数据类型错误!',
            'job_id.exists'                      => '[职业信息]不存在!',
            'age.integer'                        => '[顾客年龄]数据错误!',
            'age.between'                        => '[顾客年龄]只能在1-199之间!',
            'birthday.date_format'               => '[顾客生日]格式错误!',
            'qq.string'                          => '[联系QQ]格式错误!',
            'wechat.string'                      => '[微信号码]格式错误!',
            'marital.in'                         => '[婚姻状况]数据错误!',
            'economic_id.exists'                 => '[经济能力]不存在',
            'tags.exists'                        => '[顾客标签]数据错误!',
        ];
    }

    private function getUpdateRules(): array
    {
        $rules = [
            'id'                   => 'required|exists:customer',
            'name'                 => 'required|string|max:250',
            'sex'                  => 'required|numeric|in:1,2',
            'idcard'               => [
                'required',
                'string',
                'max:255',
                Rule::unique('customer')->ignore($this->input('id'))
            ],
            'file_number'          => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('customer')->ignore($this->input('id'))
            ],
            'sfz'                  => 'nullable|string|max:30',
            'address_id'           => 'required|integer|exists:address,id',
            'medium_id'            => 'required|integer|exists:medium,id',
            'referrer_user_id'     => [
                'required_if:medium_id,2',
                'prohibited_if:medium_id,3', // 当 medium_id 为 3 时，禁止该字段
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'referrer_customer_id' => [
                'required_if:medium_id,3',
                'prohibited_if:medium_id,2', // 当 medium_id 为 2 时，禁止该字段
                'nullable',
                'string',
                'exists:customer,id'
            ],
            'job_id'               => 'nullable|integer|exists:customer_job,id',
            'age'                  => 'nullable|integer|between:1,199',
            'birthday'             => 'nullable|date_format:Y-m-d',
            'qq'                   => 'nullable|string',
            'wechat'               => 'nullable|string',
            'marital'              => 'nullable|in:1,2,3',
            'economic_id'          => 'nullable|exists:customer_economic,id',
        ];

        // 标签是否允许为空
        if (parameter('customer_tags_required')) {
            $rules['tags'] = 'required|array|exists:tags,id';
        } else {
            $rules['tags'] = 'nullable|array|exists:tags,id';
        }

        // 需要验证联系电话
        if (user()->hasAnyAccess(['superuser', 'customer.update.phone'])) {
            $rules['phones']              = [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $relationIds = array_column($value, 'relation_id');
                    $counts      = array_count_values($relationIds);
                    if (isset($counts[1]) && $counts[1] > 1) {
                        $fail('[本人]关系电话只能设置一个。');
                    }
                    $phoneNumberMax = parameter('customer_phone_max');
                    if ($phoneNumberMax && count($value) > $phoneNumberMax) {
                        $fail("联系电话最多只能设置{$phoneNumberMax}个。");
                    }
                },
            ];
            $rules['phones.*.phone']      = [
                'required',
                'string',
                new PhoneRule($this->input('id'))
            ];
            $rules['phone.*.relation_id'] = 'required|integer|exists:customer_phone_relationships,id';
        }

        return $rules;
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'                        => '[顾客id]不能为空!',
            'id.exists'                          => '[顾客id]不存在!',
            'name.required'                      => '[顾客姓名]不能为空!',
            'name.string'                        => '[顾客姓名]格式错误!',
            'name.max'                           => '[顾客姓名]不能超过250个字符!',
            'sex.required'                       => '[顾客性别]不能为空!',
            'sex.numeric'                        => '[顾客性别]数据类型错误!',
            'sex.in'                             => '[顾客性别]数据错误!',
            'phones.required'                    => '[联系电话]不能为空!',
            'phones.array'                       => '[联系电话]格式错误!',
            'phones.*.phone.required'            => '[联系电话]不能为空!',
            'phones.*.phone.string'              => '[联系电话]格式错误!',
            'phones.*.phone.distinct'            => '[联系电话]不能重复!',
            'phones.*.relation_id.required'      => '[联系电话关系]不能为空!',
            'phones.*.relation_id.integer'       => '[联系电话关系]数据类型错误!',
            'phones.*.relation_id.exists'        => '[联系电话关系]不存在!',
            'idcard.required'                    => '[顾客卡号]不能为空!',
            'idcard.string'                      => '[顾客卡号]格式错误!',
            'idcard.max'                         => '[顾客卡号]不能超过255个字符!',
            'idcard.unique'                      => '[顾客卡号]已存在!',
            'file_number.string'                 => '[档案号码]格式错误!',
            'file_number.max'                    => '[档案号码]不能超过255个字符!',
            'file_number.unique'                 => '[档案号码]已存在!',
            'sfz.string'                         => '[身份证号]格式错误!',
            'sfz.max'                            => '[身份证号]不能超过30位!',
            'address_id.required'                => '[联系地址]不能为空!',
            'address_id.integer'                 => '[联系地址]数据类型错误!',
            'address_id.exists'                  => '[联系地址]数据错误!',
            'medium_id.required'                 => '[首次来源]不能为空!',
            'medium_id.integer'                  => '[首次来源]数据类型错误!',
            'medium_id.exists'                   => '[首次来源]不存在!',
            'referrer_user_id.required_if'       => '[推荐员工]不能为空!',
            'referrer_user_id.prohibited_if'     => '[推荐员工]必须为空!',
            'referrer_user_id.integer'           => '[推荐员工]数据类型错误!',
            'referrer_user_id.exists'            => '[推荐员工]不存在!',
            'referrer_customer_id.required_if'   => '[推荐顾客]不能为空!',
            'referrer_customer_id.prohibited_if' => '[推荐顾客]必须为空!',
            'referrer_customer_id.string'        => '[推荐顾客]数据类型错误!',
            'referrer_customer_id.exists'        => '[推荐顾客]不存在!',
            'job_id.integer'                     => '[职业信息]数据类型错误!',
            'job_id.exists'                      => '[职业信息]不存在!',
            'age.integer'                        => '[顾客年龄]数据错误!',
            'age.between'                        => '[顾客年龄]只能在1-199之间!',
            'birthday.date_format'               => '[顾客生日]格式错误!',
            'qq.string'                          => '[联系QQ]格式错误!',
            'wechat.string'                      => '[微信号码]格式错误!',
            'marital.in'                         => '[婚姻状况]数据错误!',
            'economic_id.exists'                 => '[经济能力]不存在',
            'tags.required'                      => '[顾客标签]不能为空',
            'tags.array'                         => '[顾客标签]格式错误',
            'tags.exists'                        => '[顾客标签]数据错误!',
        ];
    }

    private function getImportRules(): array
    {
        return [
            'excel' => 'required|mimes:xls,xlsx'
        ];
    }

    private function getImportMessages(): array
    {
        return [
            'excel.required' => '请选择上传的文件',
            'excel.mimes'    => '文件格式错误',
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'customer_id' => 'bail|required_without:id|exists:customer,id',
            'id'          => 'bail|required_without:customer_id|exists:customer,id',
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'customer_id.required' => '客户ID不能为空',
            'customer_id.exists'   => '没有找到顾客信息',
        ];
    }

    private function getMergeRules(): array
    {
        return [
            'customer_id'  => 'required|exists:customer,id',
            'customer_id2' => [
                'required',
                'exists:customer,id',
                function ($attribute, $value, $fail) {
                    if ($value === $this->input('customer_id')) {
                        $fail('顾客信息不能一致!');
                    }
                }
            ]
        ];
    }

    private function getMergeMessages(): array
    {
        return [
            'customer_id.required' => '主顾客信息,不能为空!',
            'customer_id.exists'   => '主顾客信息,没有找到!',
        ];
    }

    private function getQueryRules(): array
    {
        $rules = [
            'keyword'  => 'required',
            'category' => 'required|in:keyword,phone'
        ];
        // 用于ui-customer-input字段,加载顾客信息
        if ($this->input('id')) {
            $rules = [
                'id' => 'required|string|exists:customer',
            ];
        }
        return $rules;
    }

    private function getQueryMessages(): array
    {
        return [
            'keyword.required'  => '关键词不能为空!',
            'category.required' => '搜索类型不能为空!',
            'category.in'       => '搜索类型错误',
            'id.required'       => '顾客ID不能为空!',
            'id.string'         => '顾客ID格式错误!',
            'id.exists'         => '顾客信息没有找到!',
        ];
    }

    /**
     * 顾客信息表单数据
     * @param Customer|null $customer 顾客信息,更新时传入
     * @return array
     */
    public function formData(?Customer $customer = null): array
    {
        $data = [
            'name'                 => $this->input('name'),
            'sex'                  => $this->input('sex'),
            'idcard'               => $this->input('idcard'),
            'file_number'          => $this->input('file_number'),
            'sfz'                  => $this->input('sfz'),
            'address_id'           => $this->input('address_id'),
            'medium_id'            => $this->input('medium_id'),
            'referrer_user_id'     => $this->input('referrer_user_id'),
            'referrer_customer_id' => $this->input('referrer_customer_id'),
            'job_id'               => $this->input('job_id'),
            'age'                  => $this->input('age'),
            'birthday'             => $this->input('birthday'),
            'qq'                   => $this->input('qq'),
            'wechat'               => $this->input('wechat'),
            'marital'              => $this->input('marital'),
            'economic_id'          => $this->input('economic_id'),
            'remark'               => $this->input('remark'),
            'user_id'              => user()->id,    // 创建人员
            'ascription'           => $this->input('ascription', user()->id), // 开发人员
        ];

        // 自动生成卡号
        if (!$data['idcard']) {
            $data['idcard'] = date('Ymd') . str_pad((Customer::today()->count() + 1), 4, '0', STR_PAD_LEFT);
        }

        // 获取号码
        $phone = $customer
            ? $customer->phones->map(fn($customerPhone) => $customerPhone->getRawOriginal('phone'))->toArray()
            : array_column($this->input('phones'), 'phone');

        // 生成关键词搜索字段
        $data['keyword'] = Customer::generateKeyword($data, $phone);

        // 解析年纪
        if ($data['birthday'] && !$data['age']) {
            $data['age'] = Carbon::parse($data['birthday'])->age;
        }

        return $data;
    }

    /**
     * 获取电话号码变更信息
     * @param Customer $customer 当前顾客信息
     * @return array
     */
    public function getPhoneChanges(Customer $customer): array
    {
        $currentPhones = $customer->phones->keyBy('id')->toArray();
        $inputPhones   = $this->input('phones', []);

        $changes = [
            'add'    => [],     // 新增的电话
            'update' => [],  // 更新的电话
            'delete' => []   // 删除的电话
        ];

        // 收集输入中的ID，用于判断删除
        $inputIds = [];

        foreach ($inputPhones as $inputPhone) {
            // 如果有ID，说明是现有记录
            if (isset($inputPhone['id'])) {
                $inputIds[] = $inputPhone['id'];

                // 检查是否需要更新（电话号码不是隐私保护格式且与原号码不同）
                if (isset($currentPhones[$inputPhone['id']])) {
                    $currentPhone = $currentPhones[$inputPhone['id']]['phone'];
                    $newPhone     = $inputPhone['phone'];

                    // 如果新电话不包含*号（说明不是隐私保护格式）且与当前电话不同，则需要更新
                    if (!str_contains($newPhone, '*') && $currentPhone !== $newPhone) {
                        $changes['update'][] = [
                            'id'          => $inputPhone['id'],
                            'phone'       => $newPhone,
                            'relation_id' => $inputPhone['relation_id']
                        ];
                    }
                }
            } else {
                // 没有ID的是新增记录
                $changes['add'][] = [
                    'phone'       => $inputPhone['phone'],
                    'relation_id' => $inputPhone['relation_id']
                ];
            }
        }

        // 找出被删除的电话（在数据库中存在但不在输入中的）
        foreach ($currentPhones as $id => $phone) {
            if (!in_array($id, $inputIds)) {
                $changes['delete'][] = $id;
            }
        }

        return $changes;
    }
}
