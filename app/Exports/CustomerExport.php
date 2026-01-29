<?php

namespace App\Exports;

use App\Events\Web\ExportCompleted;
use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Throwable;
use Vtiful\Kernel\Excel;

class CustomerExport implements ShouldQueue
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

    public function __construct(array $request, ExportTask $task, string $tenant_id, int $user_id)
    {
        $this->task = $task;
        $this->request = $request;
        $this->user_id = $user_id;
        $this->tenant_id = $tenant_id;
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
            $headers = [
                '顾客卡号',
                '顾客姓名',
                '联系QQ',
                '微信号码',
                '身份证号',
                '职业信息',
                '经济能力',
                '婚姻状况',
                '性别',
                '生日',
                '年龄',
                '联系电话',
                '通讯地址',
                '会员等级',
                '首次来源',
                '累计付款',
                '账户余额',
                '累计消费',
                '累计欠款',
                '现有积分',
                '已用积分',
                '初诊日期',
                '最近光临',
                '最近回访',
                '开发人员',
                '现场咨询',
                '建档人员',
                '建档时间',
                '备注信息',
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 15);
            $sheet->setColumn('B:B', 20);
            $sheet->setColumn('E:E', 20);
            $sheet->setColumn('L:L', 20);

            // 写入数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->idcard,
                        $row->name,
                        $row->qq,
                        $row->wechat,
                        $row->sfz,
                        $row->job_id,
                        $row->economic_id,
                        $row->marital,
                        $row->sex == 1 ? '男' : ($row->sex == 2 ? '女' : '未知'),
                        $row->birthday,
                        $row->age,
                        $row->phones->map(fn (CustomerPhone $customerPhone) => $customerPhone->getRawOriginal('phone'))->implode(','),
                        $row->address_id,
                        $row->level_id,
                        get_medium_name($row->medium_id),
                        $row->total_payment,
                        $row->balance,
                        $row->amount,
                        $row->arrearage,
                        $row->integral,
                        $row->expend_integral,
                        $row->first_time,
                        $row->last_time,
                        $row->last_followup,
                        get_user_name($row->ascription),
                        get_user_name($row->consultant),
                        get_user_name($row->user_id),
                        $row->created_at->toDateTimeString(),
                        $row->remark,
                    ];
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

    protected function getQuery()
    {
        $keyword = $this->request['keyword'] ?? null;
        $filters = $this->request['filters'] ?? [];
        $group_id = ($this->request['group_id'] ?? 'all') === 'all' ? null : $this->request['group_id'];

        return Customer::query()
            ->select(['customer.*'])
            ->with(['phones'])
            ->when($keyword, fn (Builder $query) => $query->where('keyword', 'like', "%{$keyword}%"))
            ->when($group_id, fn (Builder $query) => $query->leftJoin('customer_group_details', 'customer_group_details.customer_id', '=', 'customer.id')
                ->where('customer_group_details.customer_group_id', $group_id)
            )
            ->queryConditions('CustomerIndex', $filters)
            // 权限限制
            ->when(! user($this->user_id)->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user($this->user_id)->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('ascription', $ids)->orWhereIn('consultant', $ids);
                });
            })
            ->orderBy('created_at', 'desc');
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
