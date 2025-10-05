<?php

namespace App\Console\Commands\Tenant;

use Artisan;
use Exception;
use Illuminate\Console\Command;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

class UpdateWebMenuCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-web-menu-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新租户旧版菜单配置';

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

        $this->info("开始更新[{$tenantName}]的Web菜单配置...");

        try {
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\Tenant\\WebMenuTableSeeder',
                '--force' => true
            ]);

            if ($exitCode === 0) {
                $this->info("[{$tenantName}]Web菜单配置更新成功");
            } else {
                $this->error("[{$tenantName}]Web菜单配置更新失败");
            }
        } catch (Exception $e) {
            $this->error("[{$tenantName}]Web菜单配置更新失败: " . $e->getMessage());
        }
    }
}
