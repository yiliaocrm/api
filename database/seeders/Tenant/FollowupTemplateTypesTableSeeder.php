<?php

namespace Database\Seeders\Tenant;

use App\Models\FollowupTemplateType;
use Illuminate\Database\Seeder;

class FollowupTemplateTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        FollowupTemplateType::truncate();
        FollowupTemplateType::create(['name' => '所有分类', 'parentid' => 0]);
    }
}
