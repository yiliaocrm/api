<?php

namespace App\Upgrades\Versions;

use App\Upgrades\Contracts\UpgradeContract;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

abstract class BaseVersion implements UpgradeContract
{
    protected ?Command $command = null;

    public function setCommand(Command $command): static
    {
        $this->command = $command;

        return $this;
    }

    // ========== 三阶段默认空实现 ==========

    public function centralUp(): void {}

    public function tenantUp(): void {}

    public function globalUp(): void {}

    // ========== 日志方法 ==========

    protected function info(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
            info($message);
        }
    }

    /**
     * 输出带租户前缀的日志
     */
    protected function tenantInfo(string $message): void
    {
        $prefix = '[租户:'.tenant()->id.' '.tenant()->name.']';
        $this->info("$prefix $message");
    }

    // ========== 幂等性辅助方法 ==========

    /**
     * 仅在表不存在时创建
     */
    protected function createTableIfNotExists(string $table, Closure $callback): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }

    /**
     * 仅在列不存在时添加
     */
    protected function addColumnIfNotExists(string $table, string $column, Closure $callback): void
    {
        if (! Schema::hasColumn($table, $column)) {
            Schema::table($table, $callback);
        }
    }

    /**
     * 仅在列存在时删除
     */
    protected function dropColumnIfExists(string $table, string $column): void
    {
        if (Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }

    /**
     * 删除表（如果存在）
     */
    protected function dropTableIfExists(string $table): void
    {
        Schema::dropIfExists($table);
    }

    /**
     * 运行 Seeder
     */
    protected function runSeeder(string $class): void
    {
        Artisan::call('db:seed', [
            '--class' => $class,
            '--force' => true,
        ]);
    }
}
