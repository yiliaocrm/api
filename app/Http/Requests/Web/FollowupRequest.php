<?php

namespace App\Http\Requests\Web;

use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;

class FollowupRequest extends FormRequest
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
            'info' => $this->getInfoRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'execute' => $this->getExecuteRules(),
            'remove' => $this->getRemoveRules(),
            'originate' => $this->getOriginateRules(),
            'batchInsert' => $this->getBatchInsertRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'info' => $this->getInfoMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'execute' => $this->getExecuteMessages(),
            'remove' => $this->getRemoveMessages(),
            'originate' => $this->getOriginateMessages(),
            'batchInsert' => $this->getBatchInsertMessages(),
            default => []
        };
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:followup',
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '回访ID不能为空',
            'id.exists'   => '回访ID不存在',
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'customer_id'   => 'required|exists:customer,id',
            'title'         => 'required',
            'date'          => 'required|date_format:Y-m-d',
            'type'          => 'required|exists:followup_type,id',
            'tool'          => 'required|exists:followup_tool,id',
            'followup_user' => 'required|exists:users,id'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'customer_id.required'   => 'customer_id参数不能为空!',
            'customer_id.exists'     => '[顾客信息]没有找到!',
            'title.required'         => '[回访主题]不能为空!',
            'date.required'          => '[提醒日期]不能为空!',
            'date.date_format'       => '[提醒日期]格式错误!',
            'type.required'          => '[回访类型]不能为空!',
            'followup_user.required' => '[提醒人员]不能为空!',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'            => 'required|exists:followup',
            'title'         => 'required',
            'date'          => 'required|date_format:Y-m-d',
            'type'          => 'required|exists:followup_type,id',
            'tool'          => 'required|exists:followup_tool,id',
            'followup_user' => 'required|exists:users,id'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'            => '回访ID不能为空!',
            'id.exists'              => '没有找到回访记录!',
            'title.required'         => '[回访主题]不能为空!',
            'date.required'          => '[提醒日期]不能为空!',
            'date.date_format'       => '[提醒日期]格式错误!',
            'type.required'          => '[回访类型]不能为空!',
            'followup_user.required' => '[提醒人员]不能为空!',
        ];
    }

    private function getExecuteRules(): array
    {
        return [
            'id'   => 'required|exists:followup',
            'tool' => 'required'
        ];
    }

    private function getExecuteMessages(): array
    {
        return [
            'id.required'   => '缺少id参数!',
            'id.exists'     => '没有找到回访记录!',
            'tool.required' => '回访工具不能为空!'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:followup'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => 'id不能为空!',
            'id.exists'   => '没有找到回访记录!'
        ];
    }

    private function getBatchInsertRules(): array
    {
        return [
            'customer_id'          => 'required|exists:customer,id',
            'data'                 => 'required|array',
            'data.*.type_id'       => 'required|exists:followup_type,id',
            'data.*.date'          => 'required|date_format:Y-m-d',
            'data.*.title'         => 'required',
            'data.*.followup_role' => 'required_without:data.*.user_id',
            'data.*.user_id'       => 'required_without:data.*.followup_role',
        ];
    }

    private function getBatchInsertMessages(): array
    {
        return [
            'customer.required'                     => 'customer_id不能为空!',
            'customer.exists'                       => '没有找到顾客信息!',
            'data.*.followup_role.required_without' => '[回访角色]与[指定人员]必填一个',
            'data.*.user_id.required_without'       => '[回访角色]与[指定人员]必填一个',
        ];
    }

    private function getOriginateRules(): array
    {
        return [
            'phone_id' => [
                'required',
                'string',
                'exists:customer_phones,id',
                function ($attribute, $value, $fail) {
                    if (!parameter('cywebos_call_center_enable')) {
                        $fail('呼叫中心功能没有打开!');
                        return;
                    }
                    if (!parameter('cywebos_call_center_api_url')) {
                        $fail('呼叫中心接口地址没有配置!');
                        return;
                    }
                }
            ]
        ];
    }

    private function getOriginateMessages(): array
    {
        return [
            'phone_id.required' => '缺少phone_id参数!',
            'phone_id.exists'   => '没有找到电话号码',
        ];
    }

    public function formData(): array
    {
        $data = [
            'type'          => $this->input('type'),
            'status'        => $this->input('remark') ? 2 : 1, // 回访状态
            'tool'          => $this->input('tool'),
            'title'         => $this->input('title'),
            'date'          => $this->input('date'),
            'time'          => $this->input('remark') ? date("Y-m-d H:i:s") : null,
            'remark'        => $this->input('remark') ?? null,
            'followup_user' => $this->input('followup_user'),
            'execute_user'  => $this->input('remark') ? user()->id : null,
            'user_id'       => user()->id,
            'callid'        => $this->input('callid'),
            'cc_cdr_id'     => null,
        ];

        // 创建回访
        if (request()->route()->getActionMethod() === 'create') {
            $data['customer_id'] = $this->input('customer_id');
        }

        // 点击拨号传入cdr_id

        return $data;
    }

    public function executeData(): array
    {
        $data = [
            'title'        => $this->input('title'),
            'type'         => $this->input('type'),
            'tool'         => $this->input('tool'),
            'time'         => date("Y-m-d H:i:s"),
            'remark'       => $this->input('remark'),
            'callid'       => $this->input('callid'),
            'execute_user' => user()->id,
            'status'       => 2,  // 标记为:已回访
        ];

        // 呼叫id

        return $data;
    }

    /**
     * 批量插入回访模板
     * @return array
     */
    public function batchInsertData(): array
    {
        $data     = [];
        $datas    = $this->input('data');
        $customer = Customer::query()->find($this->input('customer_id'));

        foreach ($datas as $v) {
            $row = $this->structFollowupData($v, $customer);
            if ($row) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * 生成回访数据
     * @param $row
     * @param $customer
     * @return array|null
     */
    private function structFollowupData($row, $customer): ?array
    {
        $followup_user = null;

        // 指定回访人员
        if ($row['user_id']) {
            $followup_user = $row['user_id'];
        }

        // 指定归属现场咨询回访
        if (!$followup_user && $row['followup_role'] && $row['followup_role'] == 'consultant' && $customer->consultant) {
            $followup_user = $customer->consultant;
        }

        // 指定归属开发人员
        if (!$followup_user && $row['followup_role'] && $row['followup_role'] == 'ascription' && $customer->ascription) {
            $followup_user = $customer->ascription;
        }

        // 指定专属客服回访
        if (!$followup_user && $row['followup_role'] && $row['followup_role'] == 'service' && $customer->service_id) {
            $followup_user = $customer->service_id;
        }

        // 指定主治医生回访
        if (!$followup_user && $row['followup_role'] && $row['followup_role'] == 'doctor' && $customer->doctor_id) {
            $followup_user = $customer->doctor_id;
        }

        if (!$followup_user) {
            return null;
        }

        return [
            'id'            => Str::uuid(),
            'customer_id'   => $customer->id,
            'type'          => $row['type_id'],
            'status'        => 1,
            'tool'          => null,
            'title'         => $row['title'],
            'date'          => $row['date'],
            'time'          => null,
            'remark'        => null,
            'followup_user' => $followup_user,
            'execute_user'  => null,
            'user_id'       => user()->id,
            'callid'        => null,
            'created_at'    => now()
        ];
    }
}
