<?php

namespace App\Exports;

use App\Events\Web\ExportCompleted;
use App\Models\CouponDetail;
use App\Models\ExportTask;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Throwable;
use Vtiful\Kernel\Excel;

class CouponDetailExport implements ShouldQueue
{
    use Queueable;

    protected int $chunkSize = 1000;

    public int $timeout = 1200;

    public function __construct(
        protected array $request,
        protected ExportTask $task,
        protected string $tenant_id,
        protected int $user_id
    ) {}

    /**
     * 异步导出领券记录数据。
     */
    public function handle(): void
    {
        try {
            $this->task->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $path = Storage::disk('public')->path(dirname($this->task->file_path));
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }

            $excel = new Excel(['path' => $path]);
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            $sheet->header([
                '状态',
                '顾客姓名',
                '顾客卡号',
                '卡券名称',
                '卡券编号',
                '卡券面值',
                '卡券余额',
                '支付金额',
                '充赠比例',
                '扣除积分',
                '过期时间',
                '发券时间',
                '备注信息',
                '发券人员',
            ]);

            $sheet->setColumn('A:A', 10);
            $sheet->setColumn('B:B', 15);
            $sheet->setColumn('C:C', 20);
            $sheet->setColumn('D:D', 40);
            $sheet->setColumn('E:E', 20);
            $sheet->setColumn('F:J', 12);
            $sheet->setColumn('K:L', 20);
            $sheet->setColumn('M:M', 30);
            $sheet->setColumn('N:N', 15);

            $this->getQuery()->chunk($this->chunkSize, function ($records) use ($sheet): void {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->status?->getLabel() ?? '',
                        $row->customer?->name ?? '',
                        $row->customer?->idcard ?? '',
                        $row->coupon_name,
                        $row->number,
                        $row->coupon_value,
                        $row->balance,
                        $row->sales_price,
                        $row->rate,
                        $row->integrals,
                        $this->formatDateTime($row->expire_time),
                        $row->created_at?->toDateTimeString() ?? '',
                        $row->remark,
                        $row->createUser?->name ?? '',
                    ];
                }

                if ($batchData !== []) {
                    $sheet->data($batchData);
                }
            });

            $sheet->output();
            $excel->close();

            $this->uploadToCloudAndDeleteLocalFile();

            $this->task->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            ExportCompleted::dispatch($this->task, $this->tenant_id, $this->user_id);
        } catch (Throwable $exception) {
            $this->task->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * 构建领券记录导出查询，保持与新版列表筛选一致。
     */
    protected function getQuery(): Builder
    {
        $sort = $this->request['sort'] ?? 'id';
        $order = $this->request['order'] ?? 'desc';
        $keyword = $this->request['keyword'] ?? null;

        return CouponDetail::query()
            ->with(['customer:id,name,idcard', 'createUser:id,name'])
            ->select('coupon_details.*')
            ->leftJoin('customer', 'customer.id', '=', 'coupon_details.customer_id')
            ->when(
                ($this->request['created_at_start'] ?? null) && ($this->request['created_at_end'] ?? null),
                function (Builder $query): void {
                    $query->whereBetween('coupon_details.created_at', [
                        Carbon::parse($this->request['created_at_start'])->startOfDay(),
                        Carbon::parse($this->request['created_at_end'])->endOfDay(),
                    ]);
                }
            )
            ->when($keyword, fn (Builder $query): Builder => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($this->request['number'] ?? null, function (Builder $query): void {
                $query->where('coupon_details.number', 'like', '%'.$this->request['number'].'%');
            })
            ->when($this->request['create_user_id'] ?? null, function (Builder $query): void {
                $query->where('coupon_details.create_user_id', $this->request['create_user_id']);
            })
            ->when($this->request['status'] ?? null, function (Builder $query): void {
                $query->where('coupon_details.status', $this->request['status']);
            })
            ->queryConditions('CouponDetailIndex', $this->request['filters'] ?? [])
            ->orderBy("coupon_details.{$sort}", $order);
    }

    /**
     * 格式化可能来自模型 cast 或原始字符串的时间字段。
     */
    protected function formatDateTime(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        return Carbon::parse($value)->toDateTimeString();
    }

    /**
     * 队列任务失败时记录失败原因。
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
     * 非本地存储时上传导出文件并删除 public 盘临时文件。
     */
    protected function uploadToCloudAndDeleteLocalFile(): void
    {
        if (Storage::getAdapter() instanceof LocalFilesystemAdapter) {
            return;
        }

        $stream = Storage::disk('public')->readStream($this->task->file_path);
        Storage::put($this->task->file_path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        Storage::disk('public')->delete($this->task->file_path);
    }
}
