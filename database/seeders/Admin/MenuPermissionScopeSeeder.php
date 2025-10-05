<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuPermissionScopeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menu_permission_scopes')->truncate();
        $scopes = [
            [
                'name'  => '查看自己',
                'slug'  => 'own',
                'order' => 1,
            ],
            [
                'name'  => '查看全部',
                'slug'  => 'all',
                'order' => 2,
            ],
            [
                'name'  => '查看归属部门',
                'slug'  => 'department',
                'order' => 3,
            ],
            [
                'name'  => '查看部门组',
                'slug'  => 'departments',
                'order' => 4,
            ],
            [
                'name'  => '查看员工组',
                'slug'  => 'users',
                'order' => 5,
            ],
        ];
        foreach ($scopes as &$scope) {
            $scope['created_at'] = now();
            $scope['updated_at'] = now();
        }
        DB::table('menu_permission_scopes')->insert($scopes);
    }
}
