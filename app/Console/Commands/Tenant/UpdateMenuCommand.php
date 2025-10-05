<?php

namespace App\Console\Commands\Tenant;

use App\Models\Tenant;
use App\Jobs\SyncMenusToTenantJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMenuCommand extends Command
{
    protected $signature = 'app:update-menu-command';

    protected $description = '更新所有租户新版菜单';

    public function handle(): void
    {
        $this->info('开始同步新版菜单到所有租户...');

        $menus                  = DB::table('menus')->get()->map(fn($item) => (array)$item)->toArray();
        $tenants                = Tenant::query()->get();
        $menu_permission_scopes = DB::table('menu_permission_scopes')->get()->map(fn($item) => (array)$item)->toArray();

        $this->info("共找到 {$tenants->count()} 个租户");

        foreach ($tenants as $tenant) {
            dispatch(new SyncMenusToTenantJob($tenant->id, $menus, $menu_permission_scopes));
            $this->info("已为租户 {$tenant->id} 派发菜单同步任务");
        }

        $this->info('所有菜单同步任务已派发完成！');
    }
}
