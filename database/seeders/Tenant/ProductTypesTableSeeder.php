<?php

namespace Database\Seeders\Tenant;

use App\Models\ProductType;
use Illuminate\Database\Seeder;

class ProductTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ProductType::query()->truncate();
        ProductType::query()->create(['id' => 1, 'name' => '项目分类', 'parentid' => 0]);
        ProductType::query()->create(['id' => 2, 'name' => '定金', 'parentid' => 1]);
    }
}
