<?php

namespace App\Exports;

use Throwable;
use Vtiful\Kernel\Excel;
use Carbon\Carbon;
use App\Models\ErkaiDetail;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class ErkaiDetailExport implements ShouldQueue
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
                '顾客姓名',
                '顾客卡号',
                '媒介来源',
                '项目/商品名称',
                '原价',
                '成交价格',
                '支付金额',
                '券支付',
                '结算科室',
                '销售人员',
                '录单人员',
                '收费时间'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 20);
            $sheet->setColumn('B:B', 15);
            $sheet->setColumn('C:C', 20);
            $sheet->setColumn('D:D', 40);
            $sheet->setColumn('E:E', 10);
            $sheet->setColumn('F:F', 10);
            $sheet->setColumn('G:G', 10);
            $sheet->setColumn('H:H', 10);
            $sheet->setColumn('I:I', 10);
            $sheet->setColumn('J:J', 15);
            $sheet->setColumn('K:K', 15);
            $sheet->setColumn('L:L', 20);

            // 查询数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->customer_name ?? '',
                        $row->customer_idcard ?? '',
                        get_medium_name($row->medium_id),
                        $row->goods_name ?? $row->product_name ?? '',
                        $row->price ?? 0,
                        $row->payable ?? 0,
                        $row->amount ?? 0,
                        $row->coupon ?? 0,
                        get_department_name($row->department_id),
                        formatter_salesman($row->salesman),
                        get_user_name($row->user_id),
                        $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '',
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

            // 触发导出完成事件,通知前端
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
        return ErkaiDetail::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'erkai.medium_id',
                'erkai_detail.goods_name',
                'erkai_detail.product_name',
                'erkai_detail.price',
                'erkai_detail.payable',
                'erkai_detail.amount',
                'erkai_detail.coupon',
                'erkai_detail.department_id',
                'erkai_detail.salesman',
                'erkai_detail.user_id',
                'erkai_detail.created_at',
            ])
            ->leftJoin('erkai', 'erkai.id', '=', 'erkai_detail.erkai_id')
            ->leftJoin('customer', 'customer.id', '=', 'erkai_detail.customer_id')
            ->where('erkai_detail.status', 3)
            ->whereBetween('erkai_detail.created_at', [
                Carbon::parse($this->request['created_at'][0])->startOfDay(),
                Carbon::parse($this->request['created_at'][1])->endOfDay()
            ])
            ->when(!empty($this->request['keyword']), function (Builder $query) {
                $query->where('customer.keyword', 'like', '%' . $this->request['keyword'] . '%');
            })
            ->queryConditions('ReportErkaiDetail', $this->request['filters'] ?? [])
            ->orderByDesc('erkai_detail.created_at');
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
