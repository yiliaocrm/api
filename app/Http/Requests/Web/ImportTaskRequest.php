<?php

namespace App\Http\Requests\Web;

use App\Enums\ExportTaskStatus;
use App\Enums\ImportTaskStatus;
use App\Models\ExportTask;
use App\Models\ImportTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportTaskRequest extends FormRequest
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
            'details' => $this->getDetailsRules(),
            'import' => $this->getImportRules(),
            'export' => $this->getExportRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'details' => $this->getDetailsMessages(),
            'import' => $this->getImportMessages(),
            'export' => $this->getExportMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    /**
     * 获取create方法的验证规则
     */
    private function getCreateRules(): array
    {
        return [
            'template_id' => 'required|integer|exists:import_templates,id',
            'file' => 'required|file|mimes:xls,xlsx',
        ];
    }

    /**
     * 获取create方法的错误消息
     */
    private function getCreateMessages(): array
    {
        return [
            'template_id.required' => '[导入模板]不能为空!',
            'template_id.integer' => '[导入模板]必须是数字!',
            'template_id.exists' => '[导入模板]不存在!',
            'file.required' => '[Excel文件]不能为空!',
            'file.file' => '[Excel文件]必须是有效的文件!',
            'file.mimes' => '[Excel文件]格式错误,仅支持xls和xlsx格式!',
        ];
    }

    /**
     * 获取details方法的验证规则
     */
    private function getDetailsRules(): array
    {
        return [
            'id' => 'required|integer|exists:import_tasks,id',
        ];
    }

    /**
     * 获取details方法的错误消息
     */
    private function getDetailsMessages(): array
    {
        return [
            'id.required' => '[任务ID]不能为空!',
            'id.integer' => '[任务ID]必须是数字!',
            'id.exists' => '[任务ID]不存在!',
        ];
    }

    /**
     * 获取import方法的验证规则
     */
    private function getImportRules(): array
    {
        return [
            'id' => 'required|integer|exists:import_tasks,id',
        ];
    }

    /**
     * 获取import方法的错误消息
     */
    private function getImportMessages(): array
    {
        return [
            'id.required' => '[任务ID]不能为空!',
            'id.integer' => '[任务ID]必须是数字!',
            'id.exists' => '[任务ID]不存在!',
        ];
    }

    /**
     * 获取export方法的验证规则
     */
    private function getExportRules(): array
    {
        return [
            'task_id' => [
                'required',
                'integer',
                'exists:import_tasks,id',
                function ($attribute, $value, $fail) {
                    $task = ImportTask::query()->find($value);
                    if ($task && $task->status->value === 1) {
                        $fail('[导入任务]正在导入中，不允许导出!');
                    }
                },
            ],
            'status' => 'nullable|integer|in:0,2',
        ];
    }

    /**
     * 获取export方法的错误消息
     */
    private function getExportMessages(): array
    {
        return [
            'task_id.required' => '[任务ID]不能为空!',
            'task_id.integer' => '[任务ID]必须是数字!',
            'task_id.exists' => '[任务ID]不存在!',
            'status.integer' => '[状态]必须是数字!',
            'status.in' => '[状态]值无效，只能是0(成功)或2(失败)!',
        ];
    }

    /**
     * 获取remove方法的验证规则
     */
    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:import_tasks,id',
                function ($attribute, $value, $fail) {
                    $task = ImportTask::query()->find($value);
                    if ($task && $task->status === ImportTaskStatus::IMPORTING) {
                        $fail('[导入任务]正在导入中，不允许删除!');
                    }
                },
            ],
        ];
    }

    /**
     * 获取remove方法的错误消息
     */
    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '[任务ID]不能为空!',
            'id.integer' => '[任务ID]必须是数字!',
            'id.exists' => '[任务ID]不存在!',
        ];
    }

    /**
     * 创建导出任务
     *
     * @param  string  $name  任务名称
     *
     * @throws ValidationException
     */
    public function createExportTask(string $name): ExportTask
    {
        $params = $this->only(['task_id', 'status']);
        $hash = md5(json_encode(array_merge($params, ['user_id' => user()->id])));

        // 检查是否存在进行中的相同导出任务
        $existingTask = ExportTask::query()
            ->where('user_id', user()->id)
            ->where('hash', $hash)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingTask) {
            throw ValidationException::withMessages([
                'export' => '任务进行中，请勿重复操作',
            ]);
        }

        // 导出文件路径
        $path = 'exports/'.date('YmdHis').'_'.Str::random(6).'.xlsx';

        return ExportTask::query()->create([
            'name' => $name,
            'hash' => $hash,
            'status' => ExportTaskStatus::PENDING,
            'params' => $params,
            'file_path' => $path,
            'user_id' => user()->id,
        ]);
    }
}
