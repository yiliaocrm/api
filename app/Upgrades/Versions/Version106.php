<?php

namespace App\Upgrades\Versions;

class Version106 extends BaseVersion
{
    /**
     * 版本号
     */
    public function version(): string
    {
        return '1.0.6';
    }

    /**
     * 全局操作
     */
    public function globalUp(): void
    {
        // TODO: 如需执行全局操作（如菜单同步），请在此方法中实现
    }
}
