<?php

namespace App\Console\Commands\Tenant;

use App\Models\Customer;
use App\Models\Admin\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class UpdateCustomerAgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-customer-age-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新顾客年龄';

    public function handle(): void
    {
        $this->info('开始更新顾客年龄...');

        $tenants           = Tenant::query()->get();
        $tenantProgressBar = $this->output->createProgressBar($tenants->count());
        $tenantProgressBar->setFormat('[%bar%] %current%/%max% 租户 (%percent:3s%%) %message%');
        $tenantProgressBar->setMessage('准备处理租户...');

        $totalUpdatedCount = 0;

        $tenants->runForEach(function ($tenant) use ($tenantProgressBar, &$totalUpdatedCount) {
            $tenantProgressBar->setMessage("处理租户: {$tenant->id}");
            $updatedCount = 0;

            // 获取今天需要更新年龄的顾客（今天生日或今天创建的顾客）
            $todayCustomersQuery = $this->getTodayCustomersQuery();
            $totalCustomers      = $todayCustomersQuery->count();

            if ($totalCustomers > 0) {
                $this->newLine();
                $customerProgressBar = $this->output->createProgressBar($totalCustomers);
                $customerProgressBar->setFormat('  [%bar%] %current%/%max% 顾客 (%percent:3s%%) %message%');
                $customerProgressBar->setMessage("租户 {$tenant->id} - 更新顾客年龄...");

                // 处理今天需要更新的顾客
                $todayCustomersQuery->chunkById(1000, function ($customers) use (&$updatedCount, $customerProgressBar) {
                    foreach ($customers as $customer) {
                        $newAge = $this->calculateAge($customer);
                        if ($newAge !== $customer->age) {
                            $customer->updateQuietly(['age' => $newAge]);
                            $updatedCount++;
                        }
                        $customerProgressBar->advance();
                    }
                });

                $customerProgressBar->setMessage("完成 - 更新了 {$updatedCount} 条记录");
                $customerProgressBar->finish();
                $this->newLine();
            }

            $totalUpdatedCount += $updatedCount;
            $tenantProgressBar->setMessage("租户 {$tenant->id} 完成 - 更新了 {$updatedCount} 条记录");
            $tenantProgressBar->advance();
        });

        $tenantProgressBar->setMessage('所有租户处理完成');
        $tenantProgressBar->finish();
        $this->newLine();
        $this->info("顾客年龄更新完成，总共更新了 {$totalUpdatedCount} 条记录");
    }

    /**
     * 获取今天需要更新年龄的顾客查询构建器
     * 查询条件：今天生日的顾客 或 今天是创建日周年的顾客
     *
     * @return Builder
     */
    private function getTodayCustomersQuery(): Builder
    {
        $today = Carbon::now();
        $month = $today->month;
        $day   = $today->day;

        return Customer::query()
            ->where(function (Builder $query) use ($month, $day) {
                // 今天生日的顾客
                $query->whereNotNull('birthday')
                    ->whereRaw('MONTH(birthday) = ? AND DAY(birthday) = ?', [$month, $day]);
            })
            ->orWhere(function (Builder $query) use ($month, $day) {
                // 今天是创建日周年的顾客（当生日为空时）
                $query->whereNull('birthday')
                    ->whereNotNull('age')
                    ->whereRaw('MONTH(created_at) = ? AND DAY(created_at) = ?', [$month, $day]);
            });
    }

    /**
     * 计算年龄
     *
     * @param Customer $customer
     * @return int
     */
    private function calculateAge(Customer $customer): int
    {
        // 如果有生日字段，直接根据生日计算年龄
        if ($customer->birthday) {
            return Carbon::parse($customer->birthday)->age;
        }

        // 如果没有生日，根据创建时间增加年龄
        $createdAt = Carbon::parse($customer->created_at);
        return $customer->age + $createdAt->diffInYears(Carbon::now());
    }
}
