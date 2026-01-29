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
            'detail', 'retry', 'cancel' => $this->getIdRules(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'workflow_id' => 'nullable|exists:workflows,id',
            'status' => 'nullable|in:running,success,error,waiting,canceled',
            'trigger_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'rows' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string',
            'order' => 'nullable|in:asc,desc',
        ];
    }

    private function getIdRules(): array
    {
        return [
            'id' => 'required|exists:workflow_executions,id',
        ];
    }
}
