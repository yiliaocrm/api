<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Admin as Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            Admin\AdminParameterSeeder::class,
            Admin\MenusTableSeeder::class,
            Admin\AdminMenusTableSeeder::class,
            Admin\MenuPermissionScopeSeeder::class,
        ]);
    }
}
