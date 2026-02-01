<?php

namespace App\Jobs;

use App\Enums\ImportTaskStatus;
use App\Imports\BaseImport;
use App\Models\ImportTask;
use App\Models\ImportTemplate;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ImportPrepareJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ImportTemplate $importTemplate,
        protected int $taskId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var BaseImport $useImport */
        $useImport = $this->importTemplate->use_import;

        $useImport->setTemplateId($this->importTemplate->id)->prepareByTaskId($this->taskId);

        // 更新任务状态为待处理
        ImportTask::query()->where('id', $this->taskId)->update([
            'status' => ImportTaskStatus::PENDING->value,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // 更新任务状态为待处理，但记录失败信息
        ImportTask::query()->where('id', $this->taskId)->update([
            'status' => ImportTaskStatus::PENDING->value,
            'validated_fail_rows' => -1, // 使用 -1 表示预检测失败
        ]);
    }

    public function unique(): mixed
    {
        return $this->taskId;
    }
}
