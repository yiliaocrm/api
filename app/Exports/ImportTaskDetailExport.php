<?php

namespace App\Exports;

use App\Enums\ExportTaskStatus;
use App\Events\Web\ExportCompleted;
use App\Models\ExportTask;
use App\Models\ImportTask;
use App\Models\ImportTaskDetail;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Throwable;
use Vtiful\Kernel\Excel;

class ImportTaskDetailExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;

    protected array $request;

    protected ?int $user_id;

    /**
     * 分批处理数据的大小
     */
    protected int $chunkSize = 1000;

    /**
     * 设置任务超时时间
     */
    public int $timeout = 1200;

    public function __construct(array $request, ExportTask $task, int $user_id)
    {
        $this->task = $task;
        $this->request = $request;
        $this->user_id = $user_id;
    }

    public function handle(): void
    {
        try {
            // 更新任务状态为处理中
            $this->task->update([
                'status' => ExportTaskStatus::PROCESSING,
                'started_at' => now(),
            ]);

            // 获取导入任务
            $importTask = ImportTask::query()->find($this->request['task_id']);
            if (! $importTask) {
                throw new Exception('导入任务不存在');
            }

            // 获取存储路径
            $path = Storage::disk('public')->path(dirname($this->task->file_path));

            // 确保目录存在
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }

            // 初始化 xlswriter
            $excel = new Excel(['path' => $path]);

            // 设置导出文件名
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            // 从 import_tasks.import_header 获取表头
            $headers = $importTask->import_header;

            // 添加状态列和错误信息列
            $headers[] = '导入状态';
            $headers[] = '校验错误';
            $headers[] = '导入错误';

            $sheet->header($headers);

            // 设置列宽
            $columnCount = count($headers);
            for ($i = 0; $i < $columnCount - 3; $i++) {
                $columnLetter = $this->getColumnLetter($i);
                $sheet->setColumn("{$columnLetter}:{$columnLetter}", 15);
            }
            // 状态列和错误信息列设置更宽
            $statusColumn = $this->getColumnLetter($columnCount - 3);
            $validateColumn = $this->getColumnLetter($columnCount - 2);
            $importColumn = $this->getColumnLetter($columnCount - 1);
            $sheet->setColumn("{$statusColumn}:{$statusColumn}", 15);
            $sheet->setColumn("{$validateColumn}:{$validateColumn}", 30);
            $sheet->setColumn("{$importColumn}:{$importColumn}", 30);

            // 写入数据
            $query = $this->getQuery($importTask);

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $headers) {
                $batchData = [];
                foreach ($records as $detail) {
                    $rowData = [];
                    // 从 row_data JSON 中按照表头顺序提取数据
                    foreach (array_slice($headers, 0, -3) as $header) {
                        $rowData[] = $detail->row_data[$header] ?? '';
                    }

                    // 添加状态
                    $rowData[] = $detail->status_text;

                    // 添加校验错误信息（JSON 转字符串）
                    $validateError = '';
                    if ($detail->validate_error_msg) {
                        $errors = json_decode($detail->validate_error_msg, true);
                        if (is_array($errors)) {
                            $validateError = implode('; ', $errors);
                        } else {
                            $validateError = $detail->validate_error_msg;
                        }
                    }
                    $rowData[] = $validateError;

                    // 添加导入错误信息
                    $rowData[] = $detail->import_error_msg ?? '';

                    $batchData[] = $rowData;
                }
                // 每一批数据直接写入文件
                if (! empty($batchData)) {
                    $sheet->data($batchData);
                }
            });

            // 导出文件
            $sheet->output();

            // 关闭文件
            $excel->close();

            // 上传到云端存储
            $this->uploadToCloudAndDeleteLocalFile();

            // 更新任务状态为完成
            $this->task->update([
                'status' => ExportTaskStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            // 触发导出完成事件，通知前端
            ExportCompleted::dispatch($this->task, tenant('id'), $this->user_id);

        } catch (Throwable $exception) {
            $this->task->update([
                'status' => ExportTaskStatus::FAILED,
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * 获取查询构建器
     */
    protected function getQuery(ImportTask $importTask): Builder
    {
        $status = $this->request['status'] ?? null;

        return ImportTaskDetail::query()
            ->where('task_id', $importTask->id)
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderBy('id', 'asc');
    }

    /**
     * 任务失败时调用
     */
    public function failed(Throwable $exception): void
    {
        $this->task->update([
            'status' => ExportTaskStatus::FAILED,
            'failed_at' => now(),
            'error_message' => '导出任务执行失败: '.$exception->getMessage(),
        ]);
    }

    /**
     * 如果不是本地存储，则上传到云端并删除本地文件
     */
    protected function uploadToCloudAndDeleteLocalFile(): void
    {
        // 如果使用的是本地存储，则不需要上传和删除
        if (Storage::getAdapter() instanceof LocalFilesystemAdapter) {
            return;
        }

        // 从本地 public 盘获取文件流
        $stream = Storage::disk('public')->readStream($this->task->file_path);

        // 将文件流式上传到默认的云存储
        Storage::put($this->task->file_path, $stream);

        // 关闭文件流
        if (is_resource($stream)) {
            fclose($stream);
        }

        // 删除本地文件
        Storage::disk('public')->delete($this->task->file_path);
    }

    /**
     * 获取列字母表示（A, B, C, ..., Z, AA, AB, ...）
     *
     * @param  int  $index  列索引（从0开始）
     */
    protected function getColumnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65).$letter;
            $index = floor($index / 26) - 1;
        }

        return $letter;
    }
}
