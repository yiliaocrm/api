<?php

namespace App\Http\Requests\Web;

use App\Models\CustomerPhone;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class SmsRequest extends FormRequest
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
            'send' => $this->getSendRules(),
            'dashboard' => $this->getDashboardRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'send' => $this->getSendMessages(),
            'dashboard' => $this->getDashboardMessages(),
            default => [],
        };
    }

    /**
     * dashboard方法的验证规则
     * @return array
     */
    private function getDashboardRules(): array
    {
        return [
            'start' => ['required', 'date'],
            'end'   => ['required', 'date', 'after_or_equal:start'],
        ];
    }

    /**
     * dashboard方法的错误消息
     * @return array
     */
    private function getDashboardMessages(): array
    {
        return [
            'start.required'     => '开始时间不能为空',
            'start.date'         => '开始时间格式不正确',
            'end.required'       => '结束时间不能为空',
            'end.date'           => '结束时间格式不正确',
            'end.after_or_equal' => '结束时间不能早于开始时间',
        ];
    }

    /**
     * send方法的验证规则
     * @return array
     */
    private function getSendRules(): array
    {
        return [
            'phone_id'    => [
                'required',
                'exists:customer_phones,id',
                function ($attribute, $value, $fail) {
                    if (!parameter('cywebos_sms_enable')) {
                        $fail('短信功能未开启，无法发送短信');
                    }
                }
            ],
            'scenario'    => 'required',
            'scenario_id' => 'required',
            'template_id' => 'required|exists:sms_templates,id',
        ];
    }

    /**
     * send方法的错误消息
     * @return array
     */
    private function getSendMessages(): array
    {
        return [
            'phone_id.required'    => '手机号ID不能为空',
            'phone_id.exists'      => '指定的手机号不存在',
            'scenario.required'    => '场景不能为空',
            'scenario_id.required' => '场景ID不能为空',
            'template_id.required' => '模板ID不能为空',
            'template_id.exists'   => '指定的短信模板不存在',
        ];
    }

    /**
     * 补全查询结果日期
     * @param $rows
     * @return array
     */
    public function formatterRows($rows): array
    {
        // 获取查询时间段
        $periods = Carbon::parse($this->input('start'))->daysUntil($this->input('end'));
        $results = [];

        // 将查询结果按日期作为键存储，方便后续查找
        $rowsByDate = [];
        foreach ($rows as $row) {
            $rowsByDate[$row->date] = $row;
        }

        // 遍历整个时间段，补全缺失日期的数据
        foreach ($periods as $period) {
            $formattedDate = $period->format('Y-m-d');

            // 如果当前日期存在于查询结果中，直接取出，否则补齐数据
            if (isset($rowsByDate[$formattedDate])) {
                $results[] = $rowsByDate[$formattedDate];
            } else {
                $results[] = [
                    'date'   => $formattedDate,
                    'total'  => 0,
                    'sent'   => 0,
                    'failed' => 0
                ];
            }
        }

        return $results;
    }

    /**
     * 短信发送任务数据
     * @return array
     */
    public function formData(): array
    {
        // 显示原始手机号
        CustomerPhone::$showOriginalPhone = true;
        $phone = CustomerPhone::query()->find($this->input('phone_id'))->phone;
        CustomerPhone::$showOriginalPhone = false;

        return [
            'template_id' => $this->input('template_id'),
            'phone'       => $phone,
            'content'     => '', // 发送内容在Job中动态生成
            'channel'     => parameter('cywebos_sms_default_gateway', 'aliyun'),
            'status'      => 'pending',
            'user_id'     => user()->id,
            'scenario'    => $this->input('scenario'),
            'scenario_id' => $this->input('scenario_id')
        ];
    }
}
