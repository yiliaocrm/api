<?php

namespace App\Console\Commands\Tenant;

use App\Models\Customer;
use Illuminate\Console\Command;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

class UpdateCustomerKeywordCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-customer-keyword-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新顾客keyword字段';

    public function __construct()
    {
        parent::__construct();
        $this->specifyParameters();
    }

    public function handle(): void
    {
        $tenantName = tenant('name');

        $this->info("当前租户: {$tenantName}");
        $this->info('开始更新顾客关键词...');

        // 获取批处理大小
        $batchSize = 1000;

        // 获取总数量
        $totalCount = Customer::query()->count();
        $this->info("共找到 {$totalCount} 个顾客记录");

        if ($totalCount === 0) {
            $this->warn('没有找到顾客记录');
            return;
        }

        // 创建进度条
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        $processedCount = 0;
        $updatedCount   = 0;
        $errorCount     = 0;

        // 分批处理
        Customer::query()
            ->with('phones')
            ->chunkById($batchSize, function ($customers) use (&$processedCount, &$updatedCount, &$errorCount, $progressBar) {
                foreach ($customers as $customer) {
                    try {
                        // 生成keyword
                        $keyword = $this->generateKeyword($customer);

                        // 更新keyword字段（不触发模型事件）
                        $customer->updateQuietly(['keyword' => $keyword]);

                        $updatedCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $this->error("更新顾客 ID: {$customer->id} 时出错: " . $e->getMessage());
                    }

                    $processedCount++;
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine(2);

        // 显示统计信息
        $this->info("更新完成！");
        $this->table(['项目', '数量'], [
            ['总记录数', $totalCount],
            ['已处理', $processedCount],
            ['成功更新', $updatedCount],
            ['错误数', $errorCount],
        ]);

        if ($errorCount > 0) {
            $this->warn("有 {$errorCount} 条记录更新失败，请查看错误信息");
        } else {
            $this->info('所有顾客关键词更新成功！');
        }
    }

    /**
     * 生成keyword字段
     *
     * @param Customer $customer
     * @return string
     */
    private function generateKeyword(Customer $customer): string
    {
        $keywordParts = array_merge(
            parse_pinyin($customer->name),
            [
                $customer->sfz,
                $customer->qq,
                $customer->wechat,
                $customer->idcard,
                $customer->file_number,
                $customer->phones->pluck('phone')->implode(','),
            ]
        );
        return implode(',', array_filter($keywordParts));
    }
}
