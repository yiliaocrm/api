<?php

namespace App\Exports;

use Throwable;
use Carbon\Carbon;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use App\Models\GoodsType;
use Illuminate\Bus\Queueable;
use App\Models\DepartmentPickingDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class DepartmentPickingDetailExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected int $user_id;

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
            $headers = $this->getHeaders();
            $sheet->header($headers);

            // 设置列宽
            $this->setColumnWidths($sheet);

            // 查询数据
            $query = $this->getQuery();

            $canViewPrice = user($this->user_id)->hasAnyAccess(['superuser', 'view.purchase.price']);

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $canViewPrice) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = $this->mapRow($row, $canViewPrice);
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

        return DepartmentPickingDetail::query()
            ->with([
                'warehouse:id,name',
                'department:id,name',
                'departmentPicking.type:id,name',
                'departmentPicking.user:id,name',
                'departmentPicking.createUser:id,name',
                'departmentPicking.auditor:id,name',
                'goods.type:id,name',
            ])
            ->select([
                'department_picking_detail.*',
            ])
            ->leftJoin('department_picking', 'department_picking.id', '=', 'department_picking_detail.department_picking_id')
            ->queryConditions('ReportDepartmentPickingDetail', $filters)
            ->when($keyword, fn(Builder $query) => $query->whereLike('department_picking_detail.goods_name', "%{$keyword}%"))
            // 审核通过
            ->where('department_picking.status', 2)
            ->orderBy("department_picking_detail.{$sort}", $order);
    }

    protected function mapRow(DepartmentPickingDetail $row, bool $canViewPrice): array
    {
        return [
            $row->date,
            $row->key,
            $row->warehouse->name,
            $row->department->name,
            $row->departmentPicking->type->name,
            $row->departmentPicking->user->name,
            $row->goods_name,
            $row->goods->type->name,
            $row->specs,
            $row->batch_code,
            $row->sncode,
            $row->number,
            $row->unit_name,
            $canViewPrice ? $row->price : '***',
            $canViewPrice ? $row->amount : '***',
            $row->manufacturer_name,
            $row->production_date,
            $row->expiry_date,
            $row->departmentPicking->remark,
            $row->remark,
            $row->departmentPicking->createUser->name,
            $row->departmentPicking->created_at->toDateTimeString(),
            $row->departmentPicking->auditor->name,
            $row->departmentPicking->check_time
        ];
    }

    protected function getHeaders(): array
    {
        return [
            '领料日期',
            '单据编号',
            '领料仓库',
            '领料科室',
            '领料类别',
            '领料人员',
            '商品名称',
            '商品类别',
            '规格',
            '批号',
            'SN码',
            '数量',
            '单位',
            '单价',
            '总价',
            '生产厂家',
            '生产日期',
            '过期时间',
            '领料备注',
            '商品备注',
            '录单人员',
            '录单时间',
            '审核人员',
            '审核时间',
        ];
    }

    protected function setColumnWidths($sheet): void
    {
        $sheet->setColumn('A:A', 12);  // 领料日期
        $sheet->setColumn('B:B', 20);  // 单据编号
        $sheet->setColumn('C:C', 12);  // 领料仓库
        $sheet->setColumn('D:D', 12);  // 领料科室
        $sheet->setColumn('E:E', 12);  // 领料类别
        $sheet->setColumn('F:F', 40);  // 领料人员
        $sheet->setColumn('G:G', 40);  // 商品名称
        $sheet->setColumn('H:H', 15);  // 规格
        $sheet->setColumn('I:I', 15);  // 批号
        $sheet->setColumn('N:N', 20);  // 生产厂家
        $sheet->setColumn('O:O', 12);  // 生产日期
        $sheet->setColumn('P:P', 12);  // 过期时间
        $sheet->setColumn('R:R', 12);  // 领料备注
        $sheet->setColumn('S:S', 20);  // 商品备注
        $sheet->setColumn('T:T', 12);  // 录单人员
        $sheet->setColumn('U:U', 20);  // 录单时间
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
