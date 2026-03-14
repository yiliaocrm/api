<?php

namespace App\Upgrades\Contracts;

interface UpgradeContract
{
    /**
     * 版本号
     */
    public function version(): string;

    /**
     * 中央数据库变更，执行一次
     */
    public function centralUp(): void;

    /**
     * 租户数据库变更，每个租户执行一次
     */
    public function tenantUp(): void;

    /**
     * 全局操作，所有租户升级后执行一次
     */
    public function globalUp(): void;
}
