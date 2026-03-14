<?php

namespace App\Console\Commands;

use App\Models\Admin\AdminParameter;
use App\Models\Admin\Tenant;
use App\Models\Admin\UpgradeLog;
use App\Upgrades\Versions\BaseVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Facades\Tenancy;

class TenancyUpgradeCommand extends Command
{
    protected $signature = 'app:tenancy-upgrade-command
        {--ver= : 只执行指定版本}
        {--tenant= : 只对指定租户执行（跳过 centralUp 和 globalUp）}
        {--dry-run : 预览待执行版本，不实际执行}
        {--force : 强制重新执行已完成的版本（必须配合 --ver 使用）}';

    protected $description = '多租户系统升级命令';

    /** @var array{success: int, error: int, skipped: int} */
    protected array $summary = ['success' => 0, 'error' => 0, 'skipped' => 0];

    public function handle(): int
    {
        // --force 必须配合 --version
        if ($this->option('force') && ! $this->option('ver')) {
            $this->error('--force 必须配合 --ver 使用，不允许无条件全量重跑');

            return self::FAILURE;
        }

        $this->info('多租户系统升级命令开始执行...');

        $versions = $this->getVersions();
        $currentVersion = admin_parameter('his_version') ?? '1.0.0';
        $isTenantMode = (bool) $this->option('tenant');
        $targetVersion = $this->option('ver');
        $isForce = $this->option('force');

        // 获取失败日志用于自动补跑
        $failedLogs = $this->getFailedLogs();

        // 过滤待执行版本
        $pendingVersions = $this->filterPendingVersions($versions, $currentVersion, $targetVersion, $isForce, $failedLogs);

        if (empty($pendingVersions)) {
            $this->info('没有待执行的升级版本');

            return self::SUCCESS;
        }

        // dry-run 模式
        if ($this->option('dry-run')) {
            $this->displayDryRun($pendingVersions, $failedLogs);

            return self::SUCCESS;
        }

        if ($isTenantMode) {
            $this->warn('--tenant 模式：仅执行 tenantUp，跳过 centralUp 和 globalUp');
        }

        // 执行升级
        foreach ($pendingVersions as $version) {
            $versionStr = $version->version();
            $this->newLine();
            $this->info("========== 开始执行版本 {$versionStr} ==========");

            $centralOk = true;
            $globalOk = true;

            // Phase 1: centralUp
            if (! $isTenantMode) {
                if ($this->shouldRunPhase($versionStr, 'central', $currentVersion, $isForce, $failedLogs)) {
                    $centralOk = $this->executeCentralPhase($version);
                    if (! $centralOk) {
                        $this->error("版本 {$versionStr} centralUp 失败，停止整个升级");

                        break;
                    }
                } else {
                    $this->info("版本 {$versionStr} centralUp 已完成，跳过");
                }
            }

            // Phase 2: tenantUp
            $this->executeTenantPhase($version, $currentVersion, $isForce, $failedLogs);

            // Phase 3: globalUp
            if (! $isTenantMode) {
                if ($this->shouldRunPhase($versionStr, 'global', $currentVersion, $isForce, $failedLogs)) {
                    $globalOk = $this->executeGlobalPhase($version);
                } else {
                    $this->info("版本 {$versionStr} globalUp 已完成，跳过");
                }
            }

            // 更新中央版本号（非 tenant 模式，且 central + global 均成功）
            if (! $isTenantMode && $centralOk && $globalOk) {
                if (version_compare($versionStr, $currentVersion, '>')) {
                    $this->updateCentralVersion($versionStr);
                    $currentVersion = $versionStr;
                }
            }

            $this->info("========== 版本 {$versionStr} 执行完成 ==========");
        }

        // 打印执行摘要
        $this->newLine();
        $this->info('升级执行摘要:');
        $this->info("  成功: {$this->summary['success']}");
        $this->info("  失败: {$this->summary['error']}");
        $this->info("  跳过: {$this->summary['skipped']}");
        $this->newLine();
        $this->info('多租户系统升级命令执行完成!');

        return $this->summary['error'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * 获取所有版本（按 version_compare 排序）
     */
    protected function getVersions(): array
    {
        $versions = [];
        $files = glob(app_path('Upgrades/Versions/*.php'));
        foreach ($files as $file) {
            $class = 'App\\Upgrades\\Versions\\'.basename($file, '.php');
            if (class_exists($class) && $class !== BaseVersion::class) {
                $version = new $class;
                $version->setCommand($this);
                $versions[] = $version;
            }
        }

        usort($versions, fn ($a, $b) => version_compare($a->version(), $b->version()));

        return $versions;
    }

    /**
     * 获取失败的升级日志
     */
    protected function getFailedLogs(): array
    {
        try {
            return UpgradeLog::query()
                ->where('status', 'error')
                ->get()
                ->groupBy(fn (UpgradeLog $log) => $log->version.'|'.$log->phase.'|'.($log->tenant_id ?? ''))
                ->keys()
                ->toArray();
        } catch (\Throwable) {
            // upgrade_logs 表可能还不存在（首次升级时）
            return [];
        }
    }

    /**
     * 过滤待执行的版本
     */
    protected function filterPendingVersions(array $versions, string $currentVersion, ?string $targetVersion, bool $isForce, array $failedLogs): array
    {
        return array_filter($versions, function ($version) use ($currentVersion, $targetVersion, $failedLogs) {
            $v = $version->version();

            // 指定版本模式
            if ($targetVersion) {
                return $v === $targetVersion;
            }

            // 新版本
            if (version_compare($v, $currentVersion, '>')) {
                return true;
            }

            // 有失败记录的版本需要补跑
            return array_any($failedLogs, fn ($key) => str_starts_with($key, $v.'|'));

        });
    }

    /**
     * 判断某阶段是否需要执行
     */
    protected function shouldRunPhase(string $version, string $phase, string $currentVersion, bool $isForce, array $failedLogs): bool
    {
        if ($isForce) {
            return true;
        }

        // 新版本，所有阶段都需要执行
        if (version_compare($version, $currentVersion, '>')) {
            return true;
        }

        // 有失败记录需要重跑
        $key = $version.'|'.$phase.'|';
        foreach ($failedLogs as $failedKey) {
            if ($failedKey === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * 执行中央数据库阶段
     */
    protected function executeCentralPhase(BaseVersion $version): bool
    {
        $versionStr = $version->version();
        $this->info("执行 {$versionStr} centralUp...");

        $log = $this->createLog($versionStr, 'central');

        try {
            Tenancy::central(function () use ($version) {
                $version->centralUp();
            });

            $log?->markSuccess();
            $this->summary['success']++;
            $this->info("{$versionStr} centralUp 执行成功");

            return true;
        } catch (\Throwable $e) {
            $log?->markError($e->getMessage(), $e->getTraceAsString());
            $this->summary['error']++;
            $this->error("{$versionStr} centralUp 执行失败: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * 执行租户数据库阶段
     */
    protected function executeTenantPhase(BaseVersion $version, string $currentVersion, bool $isForce, array $failedLogs): void
    {
        $versionStr = $version->version();
        $tenantFilter = $this->option('tenant');

        // 获取租户列表
        $query = Tenant::query();
        if ($tenantFilter) {
            $query->where('id', $tenantFilter);
        }
        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('未找到租户'.($tenantFilter ? " (ID: {$tenantFilter})" : ''));

            return;
        }

        $this->info("执行 {$versionStr} tenantUp（共 {$tenants->count()} 个租户）...");

        foreach ($tenants as $tenant) {
            // 判断该租户是否需要执行
            if (! $isForce && ! $this->shouldRunTenantUp($versionStr, $tenant, $failedLogs)) {
                $this->summary['skipped']++;

                continue;
            }

            $this->info("[租户:{$tenant->id} {$tenant->name}] 开始升级到 {$versionStr}");

            $log = $this->createLog($versionStr, 'tenant', $tenant->id, $tenant->name);

            try {
                tenancy()->initialize($tenant);
                $version->tenantUp();
                tenancy()->end();

                // 成功：更新租户版本号
                if (version_compare($versionStr, $tenant->version ?? '0.0.0', '>')) {
                    $tenant->version = $versionStr;
                    $tenant->save();
                }

                // 清除该租户对应的旧 error 日志
                $this->clearErrorLogs($versionStr, 'tenant', $tenant->id);

                $log?->markSuccess();
                $this->summary['success']++;
                $this->info("[租户:{$tenant->id} {$tenant->name}] 升级到 {$versionStr} 成功");
            } catch (\Throwable $e) {
                // 确保退出租户上下文
                try {
                    tenancy()->end();
                } catch (\Throwable) {
                }

                $log?->markError($e->getMessage(), $e->getTraceAsString());
                $this->summary['error']++;
                $this->error("[租户:{$tenant->id} {$tenant->name}] 升级到 {$versionStr} 失败: {$e->getMessage()}");
            }
        }
    }

    /**
     * 判断租户是否需要执行 tenantUp
     */
    protected function shouldRunTenantUp(string $version, object $tenant, array $failedLogs): bool
    {
        // 租户版本低于目标版本
        if (version_compare($version, $tenant->version ?? '0.0.0', '>')) {
            return true;
        }

        // 有该租户的失败记录
        $key = $version.'|tenant|'.$tenant->id;
        if (in_array($key, $failedLogs)) {
            return true;
        }

        return false;
    }

    /**
     * 执行全局操作阶段
     */
    protected function executeGlobalPhase(BaseVersion $version): bool
    {
        $versionStr = $version->version();
        $this->info("执行 {$versionStr} globalUp...");

        $log = $this->createLog($versionStr, 'global');

        try {
            Tenancy::central(function () use ($version) {
                $version->globalUp();
            });

            $log?->markSuccess();
            $this->summary['success']++;
            $this->info("{$versionStr} globalUp 执行成功");

            return true;
        } catch (\Throwable $e) {
            $log?->markError($e->getMessage(), $e->getTraceAsString());
            $this->summary['error']++;
            $this->error("{$versionStr} globalUp 执行失败: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * 更新中央版本号并清除缓存
     */
    protected function updateCentralVersion(string $version): void
    {
        Tenancy::central(function () use ($version) {
            AdminParameter::query()
                ->where('name', 'his_version')
                ->update(['value' => $version]);
        });

        // 清除 admin_parameters 缓存
        Cache::forget('admin_parameters');

        $this->info("中央版本号已更新到 {$version}，缓存已清除");
    }

    /**
     * 创建升级日志
     */
    protected function createLog(string $version, string $phase, ?string $tenantId = null, ?string $tenantName = null): ?UpgradeLog
    {
        try {
            return Tenancy::central(function () use ($version, $phase, $tenantId, $tenantName) {
                return UpgradeLog::start($version, $phase, $tenantId, $tenantName);
            });
        } catch (\Throwable) {
            // upgrade_logs 表可能还不存在
            return null;
        }
    }

    /**
     * 清除某个阶段的 error 日志
     */
    protected function clearErrorLogs(string $version, string $phase, ?string $tenantId = null): void
    {
        try {
            Tenancy::central(function () use ($version, $phase, $tenantId) {
                UpgradeLog::query()
                    ->where('version', $version)
                    ->where('phase', $phase)
                    ->where('status', 'error')
                    ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                    ->delete();
            });
        } catch (\Throwable) {
        }
    }

    /**
     * dry-run 模式输出
     */
    protected function displayDryRun(array $pendingVersions, array $failedLogs): void
    {
        $this->info('=== Dry-run 模式：以下版本将被执行 ===');
        $this->newLine();

        foreach ($pendingVersions as $version) {
            $v = $version->version();
            $this->info("  版本 {$v}");

            // 显示该版本的失败补跑信息
            foreach ($failedLogs as $key) {
                if (str_starts_with($key, $v.'|')) {
                    $parts = explode('|', $key);
                    $phase = $parts[1] ?? '';
                    $tenantId = $parts[2] ?? '';
                    $label = $tenantId ? "{$phase} (租户: {$tenantId})" : $phase;
                    $this->warn("    ↳ 补跑: {$label}");
                }
            }
        }

        $this->newLine();
        $this->info('（dry-run 模式，未实际执行）');
    }
}
