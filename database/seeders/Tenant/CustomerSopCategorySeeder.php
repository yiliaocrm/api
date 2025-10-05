<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSopCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customer_sop_categories')->truncate();
        DB::table('customer_sop_categories')->insert([
            ['name' => '默认分类', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
