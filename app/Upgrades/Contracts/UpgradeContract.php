<?php

namespace App\Upgrades\Contracts;

interface UpgradeContract
{
    /**
     * 版本号
     * @return string
     */
    public function version(): string;

    /**
     * 升级方法
     * @return void
     */
    public function upgrade(): void;
}
