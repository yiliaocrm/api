<?php

namespace App\Services;

use App\Enums\ImportTaskStatus;
use App\Imports\BaseImport;
use App\Jobs\ImportJob;
use App\Jobs\ImportPrepareJob;
use App\Models\ImportTask;
use App\Models\ImportTemplate;
use Exception;
use Illuminate\Support\Facades\Storage;

class ImportService
{
    /**
     * 执行导入任务
     */
    public function import($taskId): void
    {
        $task = ImportTask::query()->find($taskId);
        $template = ImportTemplate::query()->where('id', $task->template_id)->first();

        // 异步执行导入
        ImportJob::dispatch($template, $task);
    }

    /**
     * 执行预导入（同步）
     *
     * @throws Exception
     */
    public function prepare(ImportTemplate $template, $file): bool
    {
        if (! $template->use_import instanceof BaseImport) {
            throw new Exception('导入类不符合要求，必须继承 App\Imports\BaseImport 基类');
        }

        return $template->use_import->setTemplateId($template->id)->prepare($file);
    }

    /**
     * 创建异步预导入任务
     *
     * @throws Exception
     */
    public function prepareAsync(ImportTemplate $template, $file): int
    {
        if (! $template->use_import instanceof BaseImport) {
            throw new Exception('导入类不符合要求，必须继承 App\Imports\BaseImport 基类');
        }

        // 保存文件
        $fileSize = $file->getSize();
        $originalFileName = $file->getClientOriginalName();
        $path = Storage::disk('import')->putFile(date('Y-m-d'), $file);
        $filePath = Storage::disk('import')->path($path);

        // 创建导入任务记录，状态为预检测中
        $task = new ImportTask;
        $task->template_id = $template->id;
        $task->file_size = $fileSize;
        $task->file_name = $originalFileName;
        $task->file_path = $filePath;
        $task->file_type = $file->getClientOriginalExtension();
        $task->import_header = '[]';
        $task->status = ImportTaskStatus::PREPARING;
        $task->total_rows = 0;
        $task->create_user_id = 0;
        $task->save();

        $taskId = $task->getKey();

        // 派发异步任务执行预检测
        ImportPrepareJob::dispatch($template, $taskId);

        return $taskId;
    }
}
