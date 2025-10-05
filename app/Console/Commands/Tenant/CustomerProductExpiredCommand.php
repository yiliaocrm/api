<?php

namespace App\Console\Commands\Tenant;

use Exception;
use Carbon\Carbon;
use App\Models\CustomerProduct;
use Illuminate\Console\Command;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

class CustomerProductExpiredCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    protected $signature = 'app:customer-product-expired-command';
    protected $description = '更新过期的顾客项目状态为已过期';

    public function __construct()
    {
        parent::__construct();
        $this->specifyParameters();
    }

    public function handle(): int
    {
        $this->info('开始处理租户' . tenant('name') . '过期的顾客项目...');

        $expiredProducts = CustomerProduct::query()
            ->where('expire_time', '<', Carbon::today())
            ->whereNotIn('status', [3, 4]) // 排除已退费和已过期
            ->get();

        if ($expiredProducts->isEmpty()) {
            $this->info('没有找到过期的项目');
            return 0;
        }

        $count = $expiredProducts->count();
        $this->info("找到 {$count} 个过期项目");

        try {
            $updated = CustomerProduct::query()
                ->where('expire_time', '<', Carbon::today())
                ->whereNotIn('status', [3, 4])
                ->update(['status' => 4]);

            $this->info("处理完成! 成功更新 {$updated} 个项目状态为过期");
        } catch (Exception $e) {
            $this->error("批量更新失败: {$e->getMessage()}");
            return 1;
        }
        return 0;
    }
}
