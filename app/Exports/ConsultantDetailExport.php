<?php

namespace App\Exports;

use Throwable;
use Carbon\Carbon;
use App\Models\Reception;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class ConsultantDetailExport implements ShouldQueue
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
                '成交状态',
                '接诊类型',
                '是否接待',
                '顾客姓名',
                '顾客卡号',
                '咨询科室',
                '咨询项目',
                '未成交原因',
                '咨询备注',
                '媒介来源',
                '现场咨询',
                '二开人员',
                '助诊医生',
                '分诊接待',
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
            $sheet->setColumn('I:I', 50);
            $sheet->setColumn('M:M', 20);
            $sheet->setColumn('N:N', 20);

            // 写入数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = $this->mapRow($row);
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

    protected function getQuery(): Builder
    {
        $filters    = $this->request['filters'] ?? [];
        $keyword    = $this->request['keyword'] ?? null;
        $created_at = $this->request['created_at'];
        $sort       = $this->request['sort'] ?? 'created_at';
        $order      = $this->request['order'] ?? 'desc';

        return Reception::query()
            ->with([
                'user:id,name',
                'customer:id,name,idcard',
                'department:id,name',
                'medium:id,name',
                'consultantUser:id,name',
                'receptionType:id,name',
                'receptionItems',
                'failure:id,name',
                'ekUserRelation:id,name',
                'doctorUser:id,name',
                'receptionUser:id,name',
            ])
            ->select([
                'reception.*',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'reception.customer_id')
            ->queryConditions('ReportConsultantDetailIndex', $filters)
            ->whereBetween('reception.created_at', [
                Carbon::parse($created_at[0])->startOfDay(),
                Carbon::parse($created_at[1])->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("reception.{$sort}", $order);
    }

    protected function mapRow($row): array
    {
        $statusConfig = config('setting.reception.status');
        $statusValue  = $row->status instanceof \BackedEnum ? $row->status->value : $row->status;

        return [
            $statusConfig[$statusValue] ?? '',
            get_reception_type_name($row->type),
            $row->receptioned ? '是' : '否',
            $row->customer?->name ?? '',
            $row->customer?->idcard ?? '',
            $row->department?->name ?? '',
            get_items_name($row->items),
            $row->failure?->name ?? '',
            $row->remark ?? '',
            $row->medium?->name ?? '',
            $row->consultantUser?->name ?? '',
            $row->ekUserRelation?->name ?? '',
            $row->doctorUser?->name ?? '',
            $row->receptionUser?->name ?? '',
            $row->user?->name ?? '',
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '',
        ];
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
