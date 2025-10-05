<?php

namespace Database\Seeders\Tenant;

use App\Models\ProductPackageType;
use Illuminate\Database\Seeder;

class ProductPackageTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ProductPackageType::truncate();
        ProductPackageType::create(['name' => '所有分类', 'parentid' => 0]);
    }
}
