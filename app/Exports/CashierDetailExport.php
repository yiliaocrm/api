<?php

namespace App\Exports;

use Throwable;
use Carbon\Carbon;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use App\Models\CashierDetail;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CashierDetailExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected int $user_id;
    protected string $tenant_id;

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
            $path = Storage::disk('public')->path(dirname($this->task->file_path));

            // 确保目录存在
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            // 初始化 xlswriter
            $excel = new Excel(['path' => $path]);

            // 设置导出文件名
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            // 设置表头
            $headers = [
                '收费单号',
                '业务类型',
                '顾客姓名',
                '顾客卡号',
                '项目名称',
                '物品名称',
                '套餐名称',
                '次数/数量',
                '单位',
                '规格',
                '应收金额',
                '实收金额',
                '余额支付',
                '欠款金额',
                '结算科室',
                '收银员',
                '收款时间',
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 38);
            $sheet->setColumn('B:B', 15);
            $sheet->setColumn('C:C', 15);
            $sheet->setColumn('D:D', 20);
            $sheet->setColumn('E:E', 30);
            $sheet->setColumn('F:F', 30);
            $sheet->setColumn('G:G', 30);
            $sheet->setColumn('H:H', 12);
            $sheet->setColumn('I:I', 10);
            $sheet->setColumn('J:J', 15);
            $sheet->setColumn('K:K', 12);
            $sheet->setColumn('L:L', 12);
            $sheet->setColumn('M:M', 12);
            $sheet->setColumn('N:N', 12);
            $sheet->setColumn('O:O', 15);
            $sheet->setColumn('P:P', 15);
            $sheet->setColumn('Q:Q', 20);

            // 查询数据
            $query = $this->getQuery();

            // 获取业务类型配置
            $cashierableTypes = config('setting.cashier.cashierable_type');

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $cashierableTypes) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->id,
                        $cashierableTypes[$row->cashierable_type] ?? $row->cashierable_type,
                        $row->customer?->name ?? '',
                        $row->customer?->idcard ?? '',
                        $row->product_name,
                        $row->goods_name,
                        $row->package_name,
                        $row->times,
                        $row->unit?->name ?? '',
                        $row->specs,
                        $row->payable,
                        $row->income,
                        $row->deposit,
                        $row->arrearage,
                        $row->department?->name ?? '',
                        $row->user?->name ?? '',
                        $row->created_at?->toDateTimeString(),
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

            // 上传到云端存储
            $this->uploadToCloudAndDeleteLocalFile();

            // 更新任务状态为完成
            $this->task->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // 触发导出完成事件，通知前端
            ExportCompleted::dispatch($this->task, $this->tenant_id, $this->user_id);

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
        $sort    = $this->request['sort'] ?? 'created_at';
        $order   = $this->request['order'] ?? 'desc';
        $keyword = $this->request['keyword'] ?? null;

        return CashierDetail::query()
            ->with([
                'user:id,name',
                'unit:id,name',
                'department:id,name',
                'customer:id,name,idcard',
            ])
            ->select(['cashier_detail.*'])
            ->leftJoin('customer', 'customer.id', '=', 'cashier_detail.customer_id')
            ->queryConditions('CashierDetailIndex', $this->request['filters'] ?? [])
            ->when(isset($this->request['date']), function (Builder $query) {
                $query->whereBetween('cashier_detail.created_at', [
                    Carbon::parse($this->request['date'][0])->startOfDay(),
                    Carbon::parse($this->request['date'][1])->endOfDay()
                ]);
            })
            ->when($keyword, fn(Builder $query) => $query->whereLike('customer.keyword', '%' . $keyword . '%'))
            ->orderBy("cashier_detail.{$sort}", $order);
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
     * 如果不是本地存储,则上传到云端并删除本地文件
     */
    protected function uploadToCloudAndDeleteLocalFile(): void
    {
        // 如果使用的是本地存储,则不需要上传和删除
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
