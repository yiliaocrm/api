<?php

namespace App\Upgrades\Versions;

use Illuminate\Support\Facades\Artisan;

class Version108 extends BaseVersion
{
    /**
     * 版本号
     */
    public function version(): string
    {
        return '1.0.8';
    }

    /**
     * 全局操作
     */
    public function globalUp(): void
    {
        $this->info('执行菜单初始化数据填充');
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\Admin\\MenusTableSeeder',
            '--force' => true,
        ], $this->command->getOutput());

        $this->info('同步所有租户参数配置');
        Artisan::call('tenants:seed', [
            '--class' => 'Database\\Seeders\\Tenant\\ParametersTableSeeder',
            '--force' => true,
        ], $this->command->getOutput());

        $this->info('更新所有租户旧版菜单');
        Artisan::call('app:update-web-menu-command', [], $this->command->getOutput());

        $this->info('更新所有租户新版菜单');
        Artisan::call('app:update-menu-command', [], $this->command->getOutput());

        $this->info('更新所有租户场景化搜索字段配置');
        Artisan::call('app:update-scene-field-command', [], $this->command->getOutput());
    }
}
