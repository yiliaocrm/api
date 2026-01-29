<?php

namespace App\Upgrades\Versions;

use App\Models\Admin\AdminParameter;
use Stancl\Tenancy\Facades\Tenancy;

class Version102 extends BaseVersion
{
    /**
     * 版本号
     */
    public function version(): string
    {
        return '1.0.2';
    }

    /**
     * 升级方法
     */
    public function upgrade(): void
    {
        info('开始执行 1.0.2 版本升级');

        // 更新系统版本号到 1.0.2（在中央数据库执行）
        $this->updateHisVersion();

        info('1.0.2 版本升级完成');
    }

    /**
     * 更新系统版本号
     */
    private function updateHisVersion(): void
    {
        // 在中央数据库上下文中执行，因为 AdminParameter 是中央数据库的表
        Tenancy::central(function () {
            AdminParameter::query()
                ->where('name', 'his_version')
                ->update(['value' => '1.0.2']);
        });

        info('系统版本号已更新到 1.0.2');
    }
}
