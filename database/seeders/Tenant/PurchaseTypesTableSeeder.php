<?php

namespace Database\Seeders\Tenant;

use App\Models\PurchaseType;
use Illuminate\Database\Seeder;

class PurchaseTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        PurchaseType::truncate();

        PurchaseType::create(['id' => 1, 'name' => '常规采购']);
    }
}
