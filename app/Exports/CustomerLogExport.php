<?php

namespace App\Exports;

use Throwable;
use Carbon\Carbon;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use App\Models\CustomerLog;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CustomerLogExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected ?int $user_id;
    protected string $tenant_id;

    /**
     * 存储文件系统配置
     * @var string
     */
    protected string $disk = 'public';

    /**
     * 分批处理数据的大小
     * @var int
     */
    protected int $chunkSize = 1000;

    /**
     * 设置任务超时时间
     * @var int
     */
    public int $timeout = 1200;

    public function __construct(array $request, ExportTask $task, string $tenant_id, int $user_id)
    {
        $this->task      = $task;
        $this->request   = $request;
        $this->user_id   = $user_id;
        $this->tenant_id = $tenant_id;
    }

    public function handle(): void
    {
        try {
            // 更新任务状态为处理中
            $this->task->update([
                'status'     => 'processing',
                'started_at' => now(),
            ]);

            // 获取存储路径
            $path = Storage::disk($this->disk)->path(dirname($this->task->file_path));

            // 检查并创建目录（仅本地存储需要）
            if ($this->isLocalStorage()) {
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
            }

            // 初始化 xlswriter
            $excel = new Excel(['path' => $path]);

            // 设置导出文件名
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            // 设置表头
            $headers = [
                '顾客姓名',
                '顾客卡号',
                '操作人员',
                '操作行为',
                '业务类型',
                '业务ID',
                '变动前',
                '变动后',
                '操作时间'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 15);
            $sheet->setColumn('B:B', 15);
            $sheet->setColumn('C:C', 15);
            $sheet->setColumn('D:D', 15);
            $sheet->setColumn('E:E', 13);
            $sheet->setColumn('F:F', 13);
            $sheet->setColumn('G:G', 25);
            $sheet->setColumn('H:H', 25);
            $sheet->setColumn('I:I', 20);

            // 写入数据
            $query  = $this->getQuery();
            $action = config('setting.customer_log.action');

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $action) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->customer_name,
                        $row->customer_idcard,
                        get_user_name($row->user_id),
                        $action[$row->action] ?? $row->action,
                        $row->logable_type,
                        $row->logable_id,
                        $row->original,
                        $row->dirty,
                        $row->created_at
                    ];
                }
                // 每一批数据直接写入文件
                if (!empty($batchData)) {
                    $sheet->data($batchData);
                }
            });

            // 导出文件
            $sheet->output();

            // 关闭文件
            $excel->close();

            // 更新任务状态为完成
            $this->task->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // 触发导出完成事件
            event(new ExportCompleted($this->task, $this->tenant_id, $this->user_id));

        } catch (Throwable $exception) {
            $this->task->update([
                'status'        => 'failed',
                'failed_at'     => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    protected function getQuery(): Builder
    {
        $sort        = $this->request['sort'] ?? 'created_at';
        $order       = $this->request['order'] ?? 'desc';
        $action      = $this->request['action'] ?? null;
        $user_id     = $this->request['user_id'] ?? null;
        $created_at  = $this->request['created_at'];
        $customer_id = $this->request['customer_id'] ?? null;

        return CustomerLog::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'customer_log.user_id',
                'customer_log.action',
                'customer_log.logable_type',
                'customer_log.logable_id',
                'customer_log.original',
                'customer_log.dirty',
                'customer_log.created_at',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_log.customer_id')
            ->whereBetween('customer_log.created_at', [
                Carbon::parse($created_at[0])->startOfDay(),
                Carbon::parse($created_at[1])->endOfDay()
            ])
            ->when($action, fn(Builder $query) => $query->where('action', $action))
            ->when($user_id, fn(Builder $query) => $query->where('user_id', $user_id))
            ->when($customer_id, fn(Builder $query) => $query->where('customer_id', $customer_id))
            ->orderBy("customer_log.{$sort}", $order);
    }

    /**
     * 任务失败时调用
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $this->task->update([
            'status'        => 'failed',
            'failed_at'     => now(),
            'error_message' => '导出任务执行失败: ' . $exception->getMessage(),
        ]);
    }

    /**
     * 判断当前存储是否为本地存储
     * @return bool
     */
    protected function isLocalStorage(): bool
    {
        return Storage::disk($this->disk)->getAdapter() instanceof LocalFilesystemAdapter;
    }
}
