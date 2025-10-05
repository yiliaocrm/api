<?php

namespace App\Console\Commands\Tenant;

use Artisan;
use Exception;
use Illuminate\Console\Command;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

class UpdateCustomerGroupFieldCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-customer-group-field-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新租户客户组搜索字段配置';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->specifyParameters();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tenantName = tenant('name');

        $this->info("开始更新[{$tenantName}]的客户组搜索字段配置...");

        try {
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\Tenant\\CustomerGroupFieldsTableSeeder',
                '--force' => true
            ]);

            if ($exitCode === 0) {
                $this->info("[{$tenantName}]客户组搜索字段配置更新成功");
            } else {
                $this->error("[{$tenantName}]客户组搜索字段配置更新失败");
            }
        } catch (Exception $e) {
            $this->error("[{$tenantName}]客户组搜索字段配置更新失败: " . $e->getMessage());
        }
    }
}
