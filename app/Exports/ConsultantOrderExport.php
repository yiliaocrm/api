<?php

namespace App\Exports;

use Throwable;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\Medium;
use App\Models\ExportTask;
use Vtiful\Kernel\Excel;
use App\Models\ReceptionOrder;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class ConsultantOrderExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected ?int $user_id;

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

    public function __construct(array $request, ExportTask $task, int $user_id)
    {
        $this->task    = $task;
        $this->request = $request;
        $this->user_id = $user_id;
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
                '接诊类型',
                '成交状态',
                '顾客姓名',
                '顾客卡号',
                '咨询科室',
                '现场咨询',
                '咨询项目',
                '媒介来源',
                '类别',
                '成交项目/商品名称',
                '套餐名称',
                '次数/数量',
                '单位',
                '规格',
                '原价',
                '执行价格',
                '成交价格',
                '折扣',
                '支付金额',
                '券支付',
                '结算科室',
                '录单人员',
                '录单时间'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 10);
            $sheet->setColumn('B:B', 10);
            $sheet->setColumn('D:D', 15);
            $sheet->setColumn('E:E', 15);
            $sheet->setColumn('F:F', 15);
            $sheet->setColumn('G:G', 20);
            $sheet->setColumn('H:H', 20);
            $sheet->setColumn('I:I', 10);
            $sheet->setColumn('J:J', 10);
            $sheet->setColumn('K:K', 30);
            $sheet->setColumn('M:M', 10);
            $sheet->setColumn('N:N', 10);
            $sheet->setColumn('W:W', 20);

            // 写入数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $items = is_array($row->reception->items) ? $row->reception->items : json_decode($row->reception->items ?? '[]', true);

                    $batchData[] = [
                        $row->reception->receptionType->name ?? '',
                        $this->getStatusName($row->status),
                        $row->customer->name ?? '',
                        $row->customer->idcard ?? '',
                        $row->reception->department->name ?? '',
                        $row->reception->consultantUser->name ?? '',
                        get_items_name($items),
                        $row->reception->medium->name ?? '',
                        $row->type == 'goods' ? '商品' : '项目',
                        $row->product_name ?? $row->goods_name,
                        $row->package_name,
                        $row->times,
                        get_unit_name($row->unit_id),
                        $row->specs,
                        $row->price,
                        $row->sales_price,
                        $row->payable,
                        $this->getDiscount($row->sales_price, $row->payable),
                        $row->amount,
                        $row->coupon,
                        $row->department->name ?? '',
                        $row->user->name ?? '',
                        $row->created_at->toDateTimeString(),
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
            ExportCompleted::dispatch($this->task, tenant('id'), $this->user_id);

        } catch (Throwable $exception) {
            $this->task->update([
                'status'        => 'failed',
                'failed_at'     => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * 获取查询构建器
     * 查询逻辑与 ReportConsultantController::order() 保持一致
     */
    protected function getQuery(): Builder
    {
        $filters    = $this->request['filters'] ?? [];
        $keyword    = $this->request['keyword'] ?? null;
        $created_at = $this->request['created_at'];
        $sort       = $this->request['sort'] ?? 'created_at';
        $order      = $this->request['order'] ?? 'desc';

        return ReceptionOrder::query()
            ->with([
                'user:id,name',
                'customer:id,name,idcard',
                'department:id,name',
                'reception',
                'reception.medium:id,name',
                'reception.department:id,name',
                'reception.consultantUser:id,name',
                'reception.receptionType:id,name',
                'reception.receptionItems',
            ])
            ->select([
                'reception_order.*',
            ])
            ->leftJoin('reception', 'reception.id', '=', 'reception_order.reception_id')
            ->leftJoin('customer', 'customer.id', '=', 'reception_order.customer_id')
            ->queryConditions('ReportConsultantOrder', $filters)
            ->whereBetween('reception_order.created_at', [
                Carbon::parse($created_at[0])->startOfDay(),
                Carbon::parse($created_at[1])->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("reception_order.{$sort}", $order);
    }

    /**
     * 获取成交状态名称
     */
    protected function getStatusName($status): string
    {
        $statusList = config('setting.reception_order.status', []);
        return $statusList[$status] ?? '';
    }

    /**
     * 计算折扣
     */
    protected function getDiscount($salesPrice, $payable): string
    {
        return floatval($salesPrice) ? bcmul(bcdiv($payable, $salesPrice, 4), 100, 2) . '%' : '100%';
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
