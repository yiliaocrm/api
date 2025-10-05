<?php

namespace Database\Seeders\Tenant;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehousesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Warehouse::truncate();

        Warehouse::insert(['id' => 1, 'name' => '默认仓库', 'keyword' => 'mrck,morencangku,默认仓库', 'disabled' => 0]);
    }
}
