<?php

namespace App\Upgrades\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class Version107 extends BaseVersion
{
    /**
     * 版本号
     */
    public function version(): string
    {
        return '1.0.7';
    }

    /**
     * 租户数据库变更
     */
    public function tenantUp(): void
    {
        $this->tenantInfo('开始执行 1.0.7 版本升级');

        $this->tenantInfo('修改表 cashier_retail');
        Schema::table('cashier_retail', function (Blueprint $table) {
            $table->dropColumn('detail');
        });

        $this->tenantInfo('1.0.7 版本升级完成');
    }

    /**
     * 全局操作
     */
    public function globalUp(): void
    {
        $this->info('更新所有租户旧版菜单');
        Artisan::call('app:update-web-menu-command', [], $this->command->getOutput());
        $this->info('更新所有租户新版菜单');
        Artisan::call('app:update-menu-command', [], $this->command->getOutput());
        $this->info('更新所有租户场景化搜索字段配置');
        Artisan::call('app:update-scene-field-command', [], $this->command->getOutput());
    }
}
