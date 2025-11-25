<?php
namespace App\Imports;

use App\Models\ImportHistory;
use App\Models\ImportHistoryRecord;
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
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

abstract class BaseImport implements ToCollection, WithHeadingRow, WithChunkReading, WithValidation, WithEvents, SkipsOnFailure
{
    use SkipsFailures, RegistersEventListeners;

    /**
     * 模板 ID
     *
     * @var int
     */
    protected int $templateId = 0;

    /**
     * 文件大小
     *
     * @var int
     */
    protected int $filesize;

    /**
     * 文件地址
     *
     * @var string
     */
    protected string $file;

    /**
     * 历史记录 ID
     *
     * @var int
     */
    protected int $historyId;

    /**
     * 成功行数
     *
     * @var int
     */
    protected int $successRows = 0;

    /**
     * 模板ID
     *
     * @param $id
     * @return $this
     */
    public function setTemplateId($id): static
    {
        $this->templateId = $id;

        return $this;
    }

    /**
     * 子类必须实现的 handle 方法
     *
     * 处理实际数据入库
     *
     * @param Collection $collection
     * @return mixed
     */
    abstract protected function handle(Collection $collection): mixed;

    /**
     * 外部导入
     *
     * @param $historyId
     * @return true
     * @throws Exception
     */
    public function import($historyId): true
    {
        try {
            ImportHistoryRecord::query()
                ->where('history_id', $historyId)
                ->where('status', ImportHistoryRecord::UN_START)
                ->chunk($this->chunkSize(), function ($records) {
                    $this->handle($records);
                });
            return true;
        } catch (\Throwable $e) {
            Log::error('导入数据错误:' . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 预检测
     *
     * @param UploadedFile|string $file
     * @return bool
     * @throws Exception
     */
    public function prepare(UploadedFile|string $file): bool
    {
        // 导入前先把文件信息记录下来
        $this->saveFile($file);

        // 导入
        Excel::import($this, $this->file);

        // 更新导入历史的数据
        ImportHistory::query()->where('id', $this->historyId)->update([
            'fail_rows' => $this->dealWithFailureRows(),
            'success_rows' => $this->successRows
        ]);

        return true;
    }

    /**
     * @param UploadedFile|string $file
     * @return void
     * @throws Exception
     */
    protected function saveFile(UploadedFile|string $file): void
    {
        // 导入前先把文件信息记录下来
        if (is_string($file)) {
            if (! file_exists($file)) {
                throw new Exception("文件 {$file} 不存在");
            }
            $this->filesize = filesize($file);
            $this->file = $file;
        } else {
            $this->filesize = $file->getSize();
            $path = Storage::disk('import')->putFile(date('Y-m-d'), $file);

            $this->file = Storage::disk('import')->path($path);
        }
    }


    /**
     * 处理错误行数
     *
     * @return int
     */
    protected function dealWithFailureRows(): int
    {
        // 收集错误行数
        $failures = $this->failures();
        $failureRecords = [];
        foreach ($failures as $failure) {
            $failureRecords[] = [
                'history_id' => $this->historyId,
                'status' => ImportHistoryRecord::FAIL,
                'row_data' => json_encode($failure->values(), JSON_UNESCAPED_UNICODE),
                'error_msg' => json_encode($failure->errors(), JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        array_map(fn($record) => ImportHistoryRecord::query()->insert($record), array_chunk($failureRecords, 100));

        return count($failureRecords);
    }


    /**
     * 块的大小
     *
     * @return int
     */
    public function chunkSize(): int
    {
        $chunkSize = ImportTemplate::query()->where('id', $this->templateId)->value('chunk_size');

        return intval($chunkSize ? : 100);
    }

    /**
     * 做校验写入到 records 表里面
     *
     * @param Collection $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {
        $records = [];

        // TODO: Implement collection() method.
        foreach ($collection as $item) {
            $records[] = [
                'history_id' => $this->historyId,
                'row_data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                'status' => ImportHistoryRecord::UN_START,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (ImportHistoryRecord::query()->insert($records)) {
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
            BeforeImport::class => fn (BeforeImport $event) => $this->saveImportHistory($event)
        ];
    }

    /**
     * 导入之前保存一份导入历史的数据
     *
     * @param BeforeImport $event
     * @return void
     */
    protected function saveImportHistory(BeforeImport $event): void
    {
        $history = new ImportHistory();
        $history->template_id = $this->templateId;
        $history->file_size = $this->filesize;
        $history->import_header = json_encode(new HeadingRowImport()->toCollection($this->file)->first()->first());
        $history->file_name = pathinfo($this->file, PATHINFO_BASENAME);
        $history->file_path = $this->file;
        $history->file_type = pathinfo($this->file, PATHINFO_EXTENSION);
        $history->status = ImportHistory::UN_START;
        $history->total_rows =  array_values($event->reader->getTotalRows())[0] - 1;
        $history->create_user_id = 0;
        $history->save();

        $this->historyId = $history->getKey();
    }
}
