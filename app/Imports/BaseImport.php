<?php

namespace App\Imports;

use App\Enums\ImportTaskDetailStatus;
use App\Enums\ImportTaskStatus;
use App\Models\ImportTask;
use App\Models\ImportTaskDetail;
use App\Models\ImportTemplate;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Throwable;

abstract class BaseImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithHeadingRow, WithValidation
{
    use RegistersEventListeners, SkipsFailures;

    /**
     * 模板 ID
     */
    protected int $templateId = 0;

    /**
     * 文件大小
     */
    protected int $fileSize;

    /**
     * 文件地址
     */
    protected string $file;

    /**
     * 原始文件名
     */
    protected string $originalFileName;

    /**
     * 任务记录 ID
     */
    protected int $taskId;

    /**
     * 成功行数
     */
    protected int $successRows = 0;

    /**
     * 模板ID
     *
     * @return $this
     */
    public function setTemplateId($id): static
    {
        $this->templateId = $id;

        return $this;
    }

    /**
     * 子类必须实现的 handle 方法
     * 处理实际数据入库
     */
    abstract protected function handle(Collection $collection): mixed;

    /**
     * 外部导入
     *
     * @throws Exception
     */
    public function import($taskId): true
    {
        $this->taskId = $taskId;

        // 更新状态为导入中
        ImportTask::query()->where('id', $taskId)->update([
            'status' => ImportTaskStatus::IMPORTING,
        ]);

        try {
            ImportTaskDetail::query()
                ->where('task_id', $taskId)
                ->where('status', ImportTaskDetailStatus::PENDING)
                ->chunk($this->chunkSize(), function ($records) {
                    $this->handle($records);
                });

            // 导入完成，更新状态和统计信息
            $this->updateTaskStatus($taskId);

            return true;
        } catch (Throwable $e) {
            Log::error('导入数据错误:'.$e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 更新任务状态为已完成
     */
    protected function updateTaskStatus(int $taskId): void
    {
        $failCount = ImportTaskDetail::query()
            ->where('task_id', $taskId)
            ->where('status', ImportTaskDetailStatus::FAILED)
            ->count();

        $successCount = ImportTaskDetail::query()
            ->where('task_id', $taskId)
            ->where('status', ImportTaskDetailStatus::SUCCESS)
            ->count();

        ImportTask::query()->where('id', $taskId)->update([
            'status' => ImportTaskStatus::COMPLETED,
            'fail_rows' => $failCount,
            'success_rows' => $successCount,
        ]);
    }

    /**
     * 预检测
     *
     * @throws Exception
     */
    public function prepare(UploadedFile|string $file): bool
    {
        // 导入前先把文件信息记录下来
        $this->saveFile($file);

        // 导入
        Excel::import($this, $this->file);

        // 更新导入任务的数据
        ImportTask::query()->where('id', $this->taskId)->update([
            'fail_rows' => $this->dealWithFailureRows(),
            'success_rows' => $this->successRows,
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    protected function saveFile(UploadedFile|string $file): void
    {
        // 导入前先把文件信息记录下来
        if (is_string($file)) {
            if (! file_exists($file)) {
                throw new Exception("文件 {$file} 不存在");
            }
            $this->fileSize = filesize($file);
            $this->file = $file;
            $this->originalFileName = pathinfo($file, PATHINFO_BASENAME);
        } else {
            $this->fileSize = $file->getSize();
            $this->originalFileName = $file->getClientOriginalName();
            $path = Storage::disk('import')->putFile(date('Y-m-d'), $file);
            $this->file = Storage::disk('import')->path($path);
        }
    }

    /**
     * 处理错误行数
     */
    protected function dealWithFailureRows(): int
    {
        // 收集错误行数
        $failures = $this->failures();
        $failureRecords = [];
        foreach ($failures as $failure) {
            $failureRecords[] = [
                'task_id' => $this->taskId,
                'status' => ImportTaskDetailStatus::FAILED,
                'row_data' => json_encode($failure->values(), JSON_UNESCAPED_UNICODE),
                'error_msg' => json_encode($failure->errors(), JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        array_map(fn ($record) => ImportTaskDetail::query()->insert($record), array_chunk($failureRecords, 100));

        return count($failureRecords);
    }

    /**
     * 块的大小
     */
    public function chunkSize(): int
    {
        $chunkSize = ImportTemplate::query()->where('id', $this->templateId)->value('chunk_size');

        return intval($chunkSize ?: 100);
    }

    /**
     * 做校验写入到 records 表里面
     */
    public function collection(Collection $collection): void
    {
        $records = [];

        // TODO: Implement collection() method.
        foreach ($collection as $item) {
            $records[] = [
                'task_id' => $this->taskId,
                'row_data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                'status' => ImportTaskDetailStatus::PENDING,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (ImportTaskDetail::query()->insert($records)) {
            $this->successRows += count($records);
        }
    }

    /**
     * 注册事件
     *
     * @return mixed
     */
    public function registerEvents(): array
    {
        return [
            // Handle by a closure.
            BeforeImport::class => fn (BeforeImport $event) => $this->saveImportHistory($event),
        ];
    }

    /**
     * 导入之前保存一份导入任务的数据
     */
    protected function saveImportHistory(BeforeImport $event): void
    {
        $task = new ImportTask;
        $task->template_id = $this->templateId;
        $task->file_size = $this->fileSize;
        $task->import_header = json_encode(new HeadingRowImport()->toCollection($this->file)->first()->first());
        $task->file_name = $this->originalFileName;
        $task->file_path = $this->file;
        $task->file_type = pathinfo($this->file, PATHINFO_EXTENSION);
        $task->status = ImportTaskStatus::PENDING;
        $task->total_rows = array_values($event->reader->getTotalRows())[0] - 1;
        $task->create_user_id = 0;
        $task->save();

        $this->taskId = $task->getKey();
    }
}
