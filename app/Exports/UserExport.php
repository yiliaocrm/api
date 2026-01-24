<?php

namespace App\Exports;

use Throwable;
use App\Models\User;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class UserExport implements ShouldQueue
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
                'ID',
                '姓名',
                '账号',
                '角色',
                '归属部门',
                '个人权限',
                '分机号码',
                '动态口令',
                '在职状态',
                '参与排班',
                '备注信息',
                '创建时间',
                '最后登陆时间',
                '更新时间'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 8);
            $sheet->setColumn('B:B', 10);
            $sheet->setColumn('C:C', 15);
            $sheet->setColumn('D:D', 15);
            $sheet->setColumn('E:E', 10);
            $sheet->setColumn('F:F', 10);
            $sheet->setColumn('G:G', 10);
            $sheet->setColumn('K:K', 20);
            $sheet->setColumn('L:L', 20);
            $sheet->setColumn('M:M', 20);
            $sheet->setColumn('N:N', 20);

            // 写入数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->id,
                        $row->name,
                        $row->email,
                        $row->roles->pluck('name')->join(', '),
                        $row->department->name,
                        $row->permissions ? '有' : '无',
                        $row->extension,
                        $row->secret ? '有' : '无',
                        $row->banned ? '离职' : '在职',
                        $row->scheduleable ? '开启' : '关闭',
                        $row->remark,
                        $row->created_at->toDateTimeString(),
                        $row->last_login,
                        $row->updated_at->toDateTimeString(),
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
        $keyword       = $this->request['keyword'] ?? null;
        $roles         = $this->request['roles'] ?? null;
        $department_id = $this->request['department_id'] ?? null;

        return User::query()
            ->with([
                'roles',
                'department'
            ])
            ->select(['users.*'])
            ->when($keyword,
                fn(Builder $query) => $query->whereAny(
                    [
                        'users.remark',
                        'users.keyword',
                        'users.extension',
                    ],
                    'like',
                    '%' . $keyword . '%'
                )
            )
            ->when($roles, fn(Builder $query) => $query->leftJoin('role_users', 'users.id', '=', 'role_users.user_id')->where('role_users.role_id', $roles))
            ->when($department_id, fn(Builder $query) => $query->where('users.department_id', $department_id))
            ->orderBy('id', 'desc');
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
