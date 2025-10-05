<?php

namespace App\Http\Requests\Web;

use App\Models\ScheduleRule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'createScheduling' => $this->getCreateSchedulingRules(),
            'clearScheduling' => $this->getClearSchedulingRules(),
            'createRule' => $this->getCreateRuleRules(),
            'updateRule' => $this->getUpdateRuleRules(),
            'removeRule' => $this->getRemoveRuleRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'createScheduling' => $this->getCreateSchedulingMessages(),
            'clearScheduling' => $this->getClearSchedulingMessages(),
            'createRule' => $this->getCreateRuleMessages(),
            'updateRule' => $this->getUpdateRuleMessages(),
            'removeRule' => $this->getRemoveRuleMessages(),
            default => []
        };
    }

    private function getCreateSchedulingRules(): array
    {
        return [
            'rule_id'    => 'required|exists:schedule_rule,id',
            'title'      => 'required',
            'color'      => 'required',
            'date_start' => 'required|date',
            'date_end'   => 'required|date|after_or_equal:date_start',
            'start'      => 'required|date_format:H:i:s',
            'end'        => 'required|date_format:H:i:s',
            'user_id'    => 'required|exists:users,id'
        ];
    }

    private function getCreateSchedulingMessages(): array
    {
        return [
            'rule_id.required'        => '[班次规则]不能为空',
            'rule_id.exists'          => '[班次规则]不存在',
            'title.required'          => '[标题]不能为空',
            'color.required'          => '[颜色]不能为空',
            'date_start.required'     => '[开始日期]不能为空',
            'date_start.date'         => '[开始日期]格式错误',
            'date_end.required'       => '[结束日期]不能为空',
            'date_end.date'           => '[结束日期]格式错误',
            'date_end.after_or_equal' => '[结束日期]不能早于[开始日期]',
            'start.required'          => '[开始时间]不能为空',
            'start.date_format'       => '[开始时间]格式错误',
            'end.required'            => '[结束时间]不能为空',
            'end.date_format'         => '[结束时间]格式错误',
            'user_id.required'        => '[用户不能]为空',
            'user_id.exists'          => '[用户]不存在'
        ];
    }

    private function getClearSchedulingRules(): array
    {
        return [
            'date_start' => 'required|date',
            'date_end'   => 'required|date',
            'user_id'    => 'required|exists:users,id'
        ];
    }

    private function getClearSchedulingMessages(): array
    {
        return [
            'date_start.required' => '开始日期不能为空',
            'date_start.date'     => '开始日期格式错误',
            'date_end.required'   => '结束日期不能为空',
            'date_end.date'       => '结束日期格式错误',
            'user_id.required'    => '用户不能为空',
            'user_id.exists'      => '用户不存在'
        ];
    }

    private function getCreateRuleRules(): array
    {
        return [
            'name'   => 'required',
            'start'  => 'required',
            'end'    => 'required',
            'color'  => 'required',
            'onduty' => 'required|boolean',
        ];
    }

    private function getCreateRuleMessages(): array
    {
        return [
            'name.required'   => '班次名称不能为空',
            'start.required'  => '开始时间不能为空',
            'end.required'    => '结束时间不能为空',
            'color.required'  => '颜色不能为空',
            'onduty.required' => '是否值班不能为空',
            'onduty.boolean'  => '是否值班格式错误'
        ];
    }

    private function getUpdateRuleRules(): array
    {
        return [
            'id'     => 'required|exists:schedule_rule',
            'name'   => 'required|string',
            'onduty' => 'required|boolean',
        ];
    }

    private function getUpdateRuleMessages(): array
    {
        return [
            'id.required'     => '班次ID不能为空',
            'id.exists'       => '班次不存在',
            'name.required'   => '班次名称不能为空',
            'name.string'     => '班次名称必须是字符串',
            'onduty.required' => '是否值班不能为空',
            'onduty.boolean'  => '是否值班格式错误'
        ];
    }

    private function getRemoveRuleRules(): array
    {
        return [
            'id' => 'required|exists:schedule_rule'
        ];
    }

    private function getRemoveRuleMessages(): array
    {
        return [
            'id.required' => '班次ID不能为空',
            'id.exists'   => '班次不存在'
        ];
    }

    /**
     * 创建排班
     * @return array
     */
    public function formData(): array
    {
        $now     = Carbon::now()->toDateTimeString();
        $periods = Carbon::parse($this->input('date_start'))->daysUntil($this->input('date_end'));
        $data    = [];
        $rule    = ScheduleRule::query()->find($this->input('rule_id'));

        foreach ($periods as $period) {
            $data[] = [
                'title'      => $this->input('title'),
                'color'      => $this->input('color'),
                'start'      => $period->format('Y-m-d ' . $this->input('start')),
                'end'        => $period->format('Y-m-d ' . $this->input('end')),
                'onduty'     => $rule->onduty,
                'user_id'    => $this->input('user_id'),
                'store_id'   => store()->id,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        return $data;
    }

    /**
     * 班次规则表单数据
     * @return array
     */
    public function ruleFormData(): array
    {
        return [
            'name'     => $this->input('name'),
            'start'    => $this->input('start'),
            'end'      => $this->input('end'),
            'color'    => $this->input('color'),
            'onduty'   => $this->input('onduty', true),
            'store_id' => store()->id
        ];
    }
}
