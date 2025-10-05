<?php

namespace App\Http\Requests\Web;

use App\Models\ExportTask;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Http\FormRequest;

class DownloadRequest extends FormRequest
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
            default => [],
            'export' => $this->getExportRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'export' => $this->getExportMessages(),
        };
    }

    private function getExportRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $task = ExportTask::query()->find($value);

                    if (!$task) {
                        $fail('导出任务不存在');
                        return;
                    }

                    // 检查任务状态
                    if ($task->status !== 'completed') {
                        $fail('导出任务未完成');
                        return;
                    }

                    // 检查文件是否存在
                    if (!Storage::exists($task->file_path)) {
                        $fail('导出文件不存在');
                        return;
                    }

                    // 检查是否是当前用户的任务
                    if ($task->user_id !== user()->id) {
                        $fail('无权下载此文件');
                    }
                }
            ]
        ];
    }

    private function getExportMessages(): array
    {
        return [
            'id.required' => '任务ID不能为空',
            'id.integer'  => '任务ID必须为整数',
        ];
    }
}
