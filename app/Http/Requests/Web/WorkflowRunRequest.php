<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowRunRequest extends FormRequest
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
            'index' => $this->getIndexRules(),
            'workflows' => $this->getWorkflowsRules(),
            'detail', 'cancel' => $this->getIdRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'workflows' => $this->getWorkflowsMessages(),
            'detail', 'cancel' => $this->getIdMessages(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'workflow_id' => 'nullable|exists:workflows,id',
            'status' => 'nullable|in:pending,running,completed,canceled,error',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'rows' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|in:id,status,started_at,finished_at,total_target,processed_count,created_at,updated_at',
            'order' => 'nullable|in:asc,desc',
        ];
    }

    private function getIdRules(): array
    {
        $rules = [
            'id' => 'required|exists:workflow_runs,id',
        ];

        // 取消操作需要额外验证状态
        if (request()->route()->getActionMethod() === 'cancel') {
            $rules['id'] = [
                'required',
                'exists:workflow_runs,id',
                function ($attribute, $value, $fail) {
                    $run = \App\Models\WorkflowRun::find($value);
                    if ($run && ! in_array($run->status->value, ['pending', 'running'])) {
                        $fail('只能取消待处理或运行中的批次');
                    }
                },
            ];
        }

        return $rules;
    }

    private function getWorkflowsRules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'workflow_id.exists' => '指定的工作流不存在，请刷新后重试',
            'status.in' => '运行状态参数不正确',
            'start_date.date' => '开始日期格式不正确',
            'end_date.date' => '结束日期格式不正确',
            'end_date.after_or_equal' => '结束日期不能早于开始日期',
            'rows.integer' => '每页条数必须是整数',
            'rows.min' => '每页条数不能小于1',
            'rows.max' => '每页条数不能大于100',
            'sort.in' => '排序字段不正确',
            'order.in' => '排序方向必须是 asc 或 desc',
        ];
    }

    private function getIdMessages(): array
    {
        return [
            'id.required' => '批次ID不能为空',
            'id.exists' => '批次不存在或已删除',
        ];
    }

    private function getWorkflowsMessages(): array
    {
        return [
            'keyword.string' => '工作流关键词必须是字符串',
            'keyword.max' => '工作流关键词不能超过255个字符',
        ];
    }
}
