<?php

namespace App\Models\Admin;

use App\Models\BaseModel;

class UpgradeLog extends BaseModel
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * 创建一条 running 状态的升级日志
     */
    public static function start(string $version, string $phase, ?string $tenantId = null, ?string $tenantName = null): static
    {
        return static::query()->create([
            'version' => $version,
            'phase' => $phase,
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantName,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * 标记为成功
     */
    public function markSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'finished_at' => now(),
            'duration_ms' => $this->calculateDurationMs(),
        ]);
    }

    /**
     * 标记为失败
     */
    public function markError(string $message, ?string $trace = null): void
    {
        $this->update([
            'status' => 'error',
            'finished_at' => now(),
            'duration_ms' => $this->calculateDurationMs(),
            'error_message' => mb_substr($message, 0, 65535),
            'error_trace' => $trace ? mb_substr($trace, 0, 65535) : null,
        ]);
    }

    /**
     * 计算耗时（毫秒），确保非负
     */
    private function calculateDurationMs(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        return (int) abs(now()->diffInMilliseconds($this->started_at));
    }
}
