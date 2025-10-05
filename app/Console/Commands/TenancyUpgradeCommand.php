<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use App\Upgrades\Versions\BaseVersion;

class TenancyUpgradeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tenancy-upgrade-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '多租户系统升级命令';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('多租户系统升级命令开始执行...');

        Tenant::query()->get()->runForEach(function (Tenant $tenant) {
            $this->upgradeTenant($tenant, $this->getVersions());
        });

        $this->info('多租户系统升级命令执行完成!');
    }

    /**
     * 升级租户
     * @param Tenant $tenant 租户
     * @param array $versions 版本列表
     * @return void
     */
    protected function upgradeTenant(Tenant $tenant, array $versions): void
    {
        $this->info("租户[{$tenant->name}] 当前版本 {$tenant->version}, 升级中...");
        foreach ($versions as $version) {
            if (version_compare($version->version(), $tenant->version, '>')) {
                $this->info("租户[{$tenant->name}] 升级到版本 {$version->version()}");
                $version->upgrade();
                $tenant->version = $version->version();
                $tenant->save();
            }
        }
        $this->info("租户[{$tenant->name}] 升级完成");
    }

    /**
     * 获取所有版本
     * @return array
     */
    protected function getVersions(): array
    {
        $versions = [];
        $files    = glob(app_path('Upgrades/Versions/*.php'));
        foreach ($files as $file) {
            $class = 'App\\Upgrades\\Versions\\' . basename($file, '.php');
            if (class_exists($class) && $class !== BaseVersion::class) {
                $versions[] = new $class();
            }
        }
        return $versions;
    }
}
