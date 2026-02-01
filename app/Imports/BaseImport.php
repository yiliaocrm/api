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
use Illuminate\Support\Facades\Validator;
use Throwable;
use Vtiful\Kernel\Excel;

abstract class BaseImport
{
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
     * 校验通过行数（待导入）
     */
    protected int $pendingRows = 0;

    /**
     * 校验失败行数
     */
    protected int $validatedFailRows = 0;

    /**
     * 导入表头
     */
    protected array $importHeader = [];

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
                ->chunkById($this->chunkSize(), function ($records) {
                    $this->handle($records);
                }, 'id');

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
            'imported_rows' => $successCount,
            'imported_fail_rows' => $failCount,
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

        // 创建导入任务记录
        $this->createImportTask();

        // 使用 xlswriter 读取并处理数据
        $this->processExcelWithXlswriter();

        // 更新导入任务的数据
        ImportTask::query()->where('id', $this->taskId)->update([
            'validated_fail_rows' => $this->validatedFailRows,
            'pending_rows' => $this->pendingRows,
        ]);

        return true;
    }

    /**
     * 根据任务ID执行预检测（用于异步队列）
     *
     * @throws Exception
     */
    public function prepareByTaskId(int $taskId): bool
    {
        $this->taskId = $taskId;

        // 获取任务信息
        $task = ImportTask::query()->find($taskId);
        if (! $task) {
            throw new Exception("任务 {$taskId} 不存在");
        }

        $this->file = $task->file_path;
        $this->fileSize = $task->file_size;
        $this->originalFileName = $task->file_name;

        // 使用 xlswriter 读取并处理数据
        $this->processExcelWithXlswriter();

        // 更新导入任务的数据
        ImportTask::query()->where('id', $this->taskId)->update([
            'validated_fail_rows' => $this->validatedFailRows,
            'pending_rows' => $this->pendingRows,
        ]);

        return true;
    }

    /**
     * 使用 xlswriter 处理 Excel 文件
     *
     * @throws Exception
     */
    protected function processExcelWithXlswriter(): void
    {
        $config = ['path' => dirname($this->file)];
        $excel = new Excel($config);
        $sheet = $excel->openFile(basename($this->file))
            ->openSheet(null, Excel::SKIP_EMPTY_ROW);

        // 读取表头
        $headers = $sheet->nextRow();
        $this->importHeader = $headers;

        // 更新任务表头信息
        ImportTask::query()->where('id', $this->taskId)->update([
            'import_header' => json_encode($headers, JSON_UNESCAPED_UNICODE),
        ]);

        // 获取验证规则
        $rules = method_exists($this, 'rules') ? $this->rules() : [];
        $messages = method_exists($this, 'messages') ? $this->messages() : [];
        $attributes = method_exists($this, 'attributes') ? $this->attributes() : [];

        $records = [];
        $failures = [];
        $rowNumber = 2; // 从第2行开始（第1行是表头）

        // 逐行读取并验证
        while ($row = $sheet->nextRow()) {
            // 转换为关联数组
            $rowData = $this->combineHeadersWithRow($headers, $row);

            // 检查空行
            if ($this->isEmptyRow($rowData)) {
                $rowNumber++;

                continue;
            }

            // 验证数据
            $validationResult = $this->validateRow($rowData, $rules, $messages, $attributes);

            if ($validationResult['valid']) {
                // 验证通过
                $records[] = [
                    'task_id' => $this->taskId,
                    'row_data' => json_encode($rowData, JSON_UNESCAPED_UNICODE),
                    'status' => ImportTaskDetailStatus::PENDING,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            } else {
                // 验证失败
                $failures[] = [
                    'task_id' => $this->taskId,
                    'row_data' => json_encode($rowData, JSON_UNESCAPED_UNICODE),
                    'status' => ImportTaskDetailStatus::FAILED,
                    'validate_error_msg' => json_encode($validationResult['errors'], JSON_UNESCAPED_UNICODE),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            // 批量插入（每 100 条）
            if (count($records) >= $this->chunkSize()) {
                ImportTaskDetail::query()->insert($records);
                $this->pendingRows += count($records);
                $records = [];
            }

            // 批量插入失败记录
            if (count($failures) >= 100) {
                ImportTaskDetail::query()->insert($failures);
                $this->validatedFailRows += count($failures);
                $failures = [];
            }

            $rowNumber++;
        }

        // 插入剩余记录
        if (! empty($records)) {
            ImportTaskDetail::query()->insert($records);
            $this->pendingRows += count($records);
        }

        if (! empty($failures)) {
            ImportTaskDetail::query()->insert($failures);
            $this->validatedFailRows += count($failures);
        }

        // 更新总行数
        $totalRows = $rowNumber - 2;
        ImportTask::query()->where('id', $this->taskId)->update([
            'total_rows' => $totalRows,
        ]);
    }

    /**
     * 验证单行数据
     */
    protected function validateRow(
        array $row,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): array {
        if (empty($rules)) {
            return ['valid' => true];
        }

        $validator = Validator::make($row, $rules, $messages, $attributes);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ];
        }

        return ['valid' => true];
    }

    /**
     * 将表头和行数据合并为关联数组
     */
    protected function combineHeadersWithRow(array $headers, array $row): array
    {
        $result = [];
        foreach ($headers as $index => $header) {
            $result[$header] = $row[$index] ?? null;
        }

        return $result;
    }

    /**
     * 检查是否为空行
     */
    protected function isEmptyRow(array $rowData): bool
    {
        return collect($rowData)->filter(fn ($value) => ! empty($value))->isEmpty();
    }

    /**
     * 创建导入任务记录
     */
    protected function createImportTask(): void
    {
        $task = new ImportTask;
        $task->template_id = $this->templateId;
        $task->file_size = $this->fileSize;
        $task->file_name = $this->originalFileName;
        $task->file_path = $this->file;
        $task->file_type = pathinfo($this->file, PATHINFO_EXTENSION);
        $task->import_header = '[]'; // 初始为空数组，将在读取表头后更新
        $task->status = ImportTaskStatus::PENDING;
        $task->total_rows = 0; // 将在处理完成后更新
        $task->create_user_id = 0;
        $task->save();

        $this->taskId = $task->getKey();
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
            $this->file = $file;
            $this->fileSize = filesize($file);
            $this->originalFileName = pathinfo($file, PATHINFO_BASENAME);
        } else {
            $this->fileSize = $file->getSize();
            $this->originalFileName = $file->getClientOriginalName();
            $path = Storage::disk('import')->putFile(date('Y-m-d'), $file);
            $this->file = Storage::disk('import')->path($path);
        }
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
     * 获取验证错误消息（子类可覆盖）
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * 获取字段名称映射（子类可覆盖）
     */
    public function attributes(): array
    {
        return [];
    }
}
