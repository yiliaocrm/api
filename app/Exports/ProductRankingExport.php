<?php

namespace App\Exports;

use Throwable;
use Vtiful\Kernel\Excel;
use App\Models\Medium;
use App\Models\ExportTask;
use App\Models\ProductType;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Models\CustomerProduct;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class ProductRankingExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected int $user_id;
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
                '项目分类',
                '项目名称',
                '项目次数',
                '已用次数',
                '已退次数',
                '剩余次数',
                '应收金额',
                '实收金额',
                '余额支付',
                '卷额支付',
                '欠款金额'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 35);
            $sheet->setColumn('B:B', 35);
            $sheet->setColumn('C:K', 15);

            // 查询数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        get_tree_name(ProductType::class, $row->type_id, true),
                        $row->product_name,
                        $row->times,
                        $row->used,
                        $row->refund_times,
                        $row->leftover,
                        $row->payable,
                        $row->income,
                        $row->deposit,
                        $row->coupon,
                        $row->arrearage,
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

            // 如果使用云存储，上传文件到云端并删除本地文件
            if (!$this->isLocalStorage()) {
                $this->uploadToCloudAndDeleteLocalFile();
            }

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
        $sort      = $this->request['sort'] ?? 'income';
        $order     = $this->request['order'] ?? 'desc';
        $date      = $this->request['created_at'] ?? [];
        $type_id   = $this->request['type_id'] ?? null;
        $medium_id = $this->request['medium_id'] ?? null;

        return CustomerProduct::query()
            ->select([
                'customer_product.product_id',
                'customer_product.product_name',
                'product.type_id'
            ])
            ->selectRaw('SUM(cy_customer_product.times) AS times')
            ->selectRaw('sum(cy_customer_product.income) as income')
            ->selectRaw('sum(cy_customer_product.used) as used')
            ->selectRaw('sum(cy_customer_product.refund_times) as refund_times')
            ->selectRaw('sum(cy_customer_product.leftover) as leftover')
            ->selectRaw('sum(cy_customer_product.payable) as payable')
            ->selectRaw('sum(cy_customer_product.deposit) as deposit')
            ->selectRaw('sum(cy_customer_product.coupon) as coupon')
            ->selectRaw('sum(cy_customer_product.arrearage) as arrearage')
            ->join('product', 'customer_product.product_id', '=', 'product.id')
            ->when($date && count($date) === 2, function (Builder $query) use ($date) {
                $query->whereBetween('customer_product.created_at', [
                    Carbon::parse($date[0])->startOfDay(),
                    Carbon::parse($date[1])->endOfDay()
                ]);
            })
            ->when($type_id, fn(Builder $query) => $query->whereIn('product.type_id', ProductType::query()->find($type_id)->getAllChild()->pluck('id')))
            ->when($medium_id, fn(Builder $query) => $query->whereIn('customer_product.medium_id', Medium::query()->find($medium_id)->getAllChild()->pluck('id')))
            ->groupBy('customer_product.product_id', 'customer_product.product_name', 'product.type_id')
            ->orderBy($sort, $order);
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

    /**
     * 上传文件到云端存储并删除本地文件
     *
     * 当使用云存储（如 OSS、S3）时，xlswriter 生成的本地文件需要上传到云端，
     * 然后删除本地临时文件以节省服务器存储空间。
     */
    protected function uploadToCloudAndDeleteLocalFile(): void
    {
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
