<?php

namespace App\Upgrades\Versions;

use Illuminate\Support\Facades\Artisan;

class Version104 extends BaseVersion
{
    /**
     * 版本号
     */
    public function version(): string
    {
        return '1.0.4';
    }

    /**
     * 全局操作
     */
    public function globalUp(): void
    {
        $this->info('更新所有租户旧版菜单');
        Artisan::call('app:update-web-menu-command');
    }
}
