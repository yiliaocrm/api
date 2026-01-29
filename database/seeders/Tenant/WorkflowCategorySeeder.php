<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('workflow_categories')->truncate();
        DB::table('workflow_categories')->insert([
            ['name' => '默认分类', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
