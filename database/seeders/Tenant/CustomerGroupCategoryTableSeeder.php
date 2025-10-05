<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerGroupCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('customer_group_categories')->truncate();
        DB::table('customer_group_categories')->insert([
            'id'         => 1,
            'name'       => '默认分类',
            'sort'       => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
