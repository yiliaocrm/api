<?php

namespace App\Exports;

use App\Events\Web\ExportCompleted;
use App\Models\Accounts;
use App\Models\Cashier;
use App\Models\ExportTask;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Throwable;
use Vtiful\Kernel\Excel;

class ReportCashierListExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;

    protected array $request;

    protected int $user_id;

    protected string $tenant_id;

    /**
     * 分批处理数据的大小
     */
    protected int $chunkSize = 1000;

    /**
     * 设置任务超时时间
     */
    public int $timeout = 1200;

    /**
     * 收费账户
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $accounts;

    public function __construct(array $request, ExportTask $task, string $tenant_id, int $user_id)
    {
        $this->task = $task;
        $this->request = $request;
        $this->user_id = $user_id;
        $this->tenant_id = $tenant_id;
        $this->accounts = Accounts::query()->orderBy('id', 'asc')->get();
    }

    public function handle(): void
    {
        try {
            // 更新任务状态为处理中
            $this->task->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

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

            // 设置表头
            $accounts_name = $this->accounts->pluck('name')->toArray();
            $headers = [
                '收费单号',
                '单据编号',
                '顾客姓名',
                '顾客卡号',
                '录单人员',
                '收费人员',
                '业务单数',
                '业务类型',
                '应收金额',
                '实收金额',
                ...$accounts_name,
                '录单时间',
                '收费时间',
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 40);
            $sheet->setColumn('B:B', 20);
            $sheet->setColumn('D:D', 15);
            $sheet->setColumn('E:E', 15);
            $sheet->setColumn('F:F', 15);
            $sheet->setColumn('H:H', 10);

            // 写入数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = $this->mapRow($row);
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
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // 触发导出完成事件，通知前端
            ExportCompleted::dispatch($this->task, tenant('id'), $this->user_id);

        } catch (Throwable $exception) {
            $this->task->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    protected function getQuery(): Builder
    {
        $filters = $this->request['filters'] ?? [];
        $keyword = $this->request['keyword'] ?? null;
        $created_at = $this->request['created_at'];
        $sort = $this->request['sort'] ?? 'created_at';
        $order = $this->request['order'] ?? 'desc';

        return Cashier::query()
            ->with([
                'pay',
                'customer:id,name,idcard',
                'user:id,name',
                'operatorUser:id,name',
            ])
            ->select('cashier.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier.customer_id')
            ->where('status', 2)
            ->whereBetween('cashier.created_at', [
                Carbon::parse($created_at[0])->startOfDay(),
                Carbon::parse($created_at[1])->endOfDay(),
            ])
            ->when($keyword, fn (Builder $query) => $query->where('customer.keyword', 'like', '%'.$keyword.'%'))
            ->queryConditions('ReportCashierList', $filters)
            ->orderBy("cashier.{$sort}", $order);
    }

    protected function mapRow($row): array
    {
        $type = config('setting.cashier.cashierable_type');
        $accounts = [];

        $this->accounts->each(function ($v) use ($row, &$accounts) {
            array_push($accounts, $row->pay->where('accounts_id', $v->id)->sum('income'));
        });

        return [
            $row->id,
            $row->key,
            $row->customer?->name,
            $row->customer?->idcard ?? '',
            $row->user?->name,
            $row->operatorUser?->name,
            $row->cashierable_type == 'App\\Models\\Recharge' ? 1 : count($row->detail ?? []),
            $type[$row->cashierable_type] ?? '',
            $row->payable,
            $row->income,
            ...$accounts,
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '',
            $row->updated_at ? $row->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * 任务失败时调用
     */
    public function failed(Throwable $exception): void
    {
        $this->task->update([
            'status' => 'failed',
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
}
