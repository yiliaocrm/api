<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowExecutionRequest extends FormRequest
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
            'detail', 'retry', 'cancel' => $this->getIdRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'workflows' => $this->getWorkflowsMessages(),
            'detail', 'retry', 'cancel' => $this->getIdMessages(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'execution_id' => 'nullable|exists:workflow_executions,id',
            'workflow_id' => 'nullable|exists:workflows,id',
            'workflow_version_id' => 'nullable|exists:workflow_versions,id',
            'latest_version_only' => 'nullable|boolean',
            'status' => 'nullable|in:running,success,error,waiting,canceled',
            'trigger_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'rows' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string',
            'order' => 'nullable|string',
        ];
    }

    private function getIdRules(): array
    {
        return [
            'id' => 'required|exists:workflow_executions,id',
        ];
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
            'execution_id.exists' => '指定的执行记录不存在，请刷新后重试',
            'workflow_id.exists' => '指定的工作流不存在，请刷新后重试',
            'workflow_version_id.exists' => '指定的工作流版本不存在，请刷新后重试',
            'latest_version_only.boolean' => '版本过滤参数格式不正确',
            'status.in' => '执行状态参数不正确',
            'start_date.date' => '开始日期格式不正确',
            'end_date.date' => '结束日期格式不正确',
            'end_date.after_or_equal' => '结束日期不能早于开始日期',
            'rows.integer' => '每页条数必须是整数',
            'rows.min' => '每页条数不能小于1',
            'rows.max' => '每页条数不能大于100',
        ];
    }

    private function getIdMessages(): array
    {
        return [
            'id.required' => '执行记录ID不能为空',
            'id.exists' => '执行记录不存在或已删除',
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
