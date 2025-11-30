<?php

namespace App\Exports;

use Throwable;
use Carbon\Carbon;
use Vtiful\Kernel\Excel;
use App\Models\Treatment;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class TreatmentRecordExport implements ShouldQueue
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
                '状态',
                '顾客姓名',
                '顾客卡号',
                '项目分类',
                '项目名称',
                '套餐名称',
                '扣划次数',
                '扣划金额',
                '欠款金额',
                '执行科室',
                '扣划备注',
                '配台人员',
                '划扣人员',
                '扣划时间',
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 10);
            $sheet->setColumn('B:B', 15);
            $sheet->setColumn('C:C', 15);
            $sheet->setColumn('D:D', 15);
            $sheet->setColumn('E:E', 50);
            $sheet->setColumn('F:F', 30);
            $sheet->setColumn('M:M', 20);

            // 查询数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];

                foreach ($records as $row) {
                    $batchData[] = [
                        $row->status?->getLabel() ?? '',
                        $row->customer_name ?? '',
                        $row->customer_idcard ?? '',
                        $row->product?->type?->name ?? '',
                        $row->product_name ?? '',
                        $row->package_name ?? '',
                        $row->times ?? '',
                        $row->price ?? '',
                        $row->arrearage ?? '',
                        $row->department?->name ?? '',
                        $row->remark ?? '',
                        $row->treatmentParticipants?->pluck('user.name')->implode(',') ?? '',
                        $row->user?->name ?? '',
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
        $keyword = $this->request['keyword'] ?? null;
        $date    = $this->request['date'] ?? null;
        $filters = $this->request['filters'] ?? [];

        return Treatment::query()
            ->select([
                'treatment.*',
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
            ])
            ->with([
                'user:id,name',
                'department:id,name',
                'treatmentParticipants.user:id,name',
                'product:id,type_id',
                'product.type:id,name',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'treatment.customer_id')
            ->queryConditions('TreatmentRecord', $filters)
            ->when($date && isset($date[0]) && isset($date[1]), function ($query) use ($date) {
                $query->whereBetween('treatment.created_at', [
                    Carbon::parse($date[0])->startOfDay(),
                    Carbon::parse($date[1])->endOfDay()
                ]);
            })
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            // 限制查询权限
            ->when(!user($this->user_id)->hasAnyAccess(['superuser', 'treatment.view.all']), function ($query) {
                $ids = user($this->user_id)->getTreatmentViewDepartmentsPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('treatment.department_id', $ids);
                });
            })
            ->orderBy('treatment.created_at', 'desc');
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
