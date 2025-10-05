<?php

namespace Database\Seeders\Tenant;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Item::query()->truncate();
        Item::query()->create(['id' => 1, 'name' => '咨询项目', 'parentid' => 0]);
        Item::query()->create(['id' => 2, 'name' => '定金', 'parentid' => 1]);
    }
}
