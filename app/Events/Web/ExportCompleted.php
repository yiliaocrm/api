<?php

namespace App\Events\Web;

use App\Models\ExportTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ExportTask $task;
    public int $user_id;
    public string $tenant_id;

    /**
     * Create a new event instance.
     */
    public function __construct(ExportTask $task, string $tenant_id, int $user_id)
    {
        $this->task      = $task;
        $this->user_id   = $user_id;
        $this->tenant_id = $tenant_id;
    }

    /**
     * 广播事件的频道
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("{$this->tenant_id}.export.{$this->user_id}");
    }

    /**
     * 广播事件携带的数据
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'task_id'   => $this->task->id,
            'name'      => $this->task->name,
            'file_path' => $this->task->file_path,
            'status'    => $this->task->status,
//            'completed_at' => $this->task->completed_at?->toDateTimeString(),
            'message'   => '导出任务已完成'
        ];
    }

    /**
     * 广播事件的名称
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'export.completed';
    }

    /**
     * 确定此事件是否应该广播
     * @return bool
     */
    public function broadcastWhen(): bool
    {
        // 因sentinel在那时不支持广播,这里注释
        return false;
    }
}
