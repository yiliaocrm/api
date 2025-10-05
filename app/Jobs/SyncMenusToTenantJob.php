<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class SyncMenusToTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $tenant_id 租户id
     * @param array $menus 菜单数组
     * @param array $menu_permission_scopes 菜单权限范围
     */
    public function __construct(
        protected string $tenant_id,
        protected array  $menus,
        protected array $menu_permission_scopes
    )
    {
    }

    /**
     * @return void
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function handle(): void
    {
        tenancy()->initialize($this->tenant_id);
        DB::table('menus')->truncate();
        DB::table('menus')->insert($this->menus);
        DB::table('menu_permission_scopes')->truncate();
        DB::table('menu_permission_scopes')->insert($this->menu_permission_scopes);
        tenancy()->end();
    }
}
