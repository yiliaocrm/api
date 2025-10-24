<?php

namespace App\Exports;

use Throwable;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Models\CustomerProduct;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CustomerProductExport implements ShouldQueue
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
                '姓名',
                '卡号',
                '收费单号',
                '性别',
                '年龄',
                '项目名称',
                '套餐名称',
                '状态',
                '过期时间',
                '项目次数',
                '已用次数',
                '剩余次数',
                '已退次数',
                '原价',
                '成交金额',
                '折扣',
                '实收金额',
                '余额支付',
                '卷支付',
                '欠款金额',
                '收费人员',
                '现场咨询',
                '二开人员',
                '接诊医生',
                '接诊类型',
                '媒介来源',
                '结算科室',
                '录入时间'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 15);
            $sheet->setColumn('B:B', 15);
            $sheet->setColumn('C:C', 40);
            $sheet->setColumn('E:E', 5);
            $sheet->setColumn('F:F', 40);
            $sheet->setColumn('G:G', 40);
            $sheet->setColumn('V:V', 20);
            $sheet->setColumn('X:X', 15);
            $sheet->setColumn('Y:Y', 20);

            // 查询数据
            $query = $this->getQuery();

            $setting = config('setting.customer_product.status');

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $setting) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->name,
                        $row->idcard,
                        $row->cashier_id,
                        $row->sex == 1 ? '男' : '女',
                        $row->age,
                        $row->product_name,
                        $row->package_name,
                        $setting[$row->status],
                        $row->expire_time,
                        $row->times,
                        $row->used,
                        $row->leftover,
                        $row->refund_times,
                        $row->price,
                        $row->payable,
                        floatval($row->price) ? bcmul(bcdiv($row->payable, $row->price, 4), 100, 2) . '%' : '100%',
                        $row->income,
                        $row->deposit,
                        $row->coupon,
                        $row->arrearage,
                        get_user_name($row->user_id),
                        get_user_name($row->consultant),
                        get_user_name($row->ek_user),
                        get_user_name($row->doctor),
                        get_reception_type_name($row->reception_type),
                        get_medium_name($row->medium_id),
                        get_department_name($row->department_id),
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
        $filters = $this->request['filters'] ?? [];

        return CustomerProduct::query()
            ->select([
                'customer.sex',
                'customer.age',
                'customer.name',
                'customer.idcard',
                'customer_product.*'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_product.customer_id')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->queryConditions('ReportCustomerProduct', $filters)
            ->orderBy("customer_product.{$sort}", $order);
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
