<?php

namespace App\Services;

use App\Imports\BaseImport;
use App\Jobs\ImportJob;
use App\Models\ImportTask;
use App\Models\ImportTemplate;
use Exception;

class ImportService
{
    /**
     * 执行导入任务
     */
    public function import($taskId): void
    {
        $task = ImportTask::query()->find($taskId);
        $template = ImportTemplate::query()->where('id', $task->template_id)->first();

        // 是否使用异步导入，如果校验通过行数 > async_limit 限制
        if ($task->pending_rows > $template->async_limit) {
            ImportJob::dispatch($template, $task);
        } else {
            $template->use_import->import($taskId);
        }
    }

    /**
     * 执行预导入
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
}
