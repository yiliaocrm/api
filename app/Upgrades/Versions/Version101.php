<?php

namespace App\Upgrades\Versions;

class Version101 extends BaseVersion
{
    /**
     * 版本号
     * @return string
     */
    public function version(): string
    {
        return '1.0.1';
    }

    /**
     * 升级方法
     * @return void
     */
    public function upgrade(): void
    {
        info('升级到版本 1.0.1');
    }
}
