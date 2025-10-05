<?php

namespace Database\Seeders\Tenant;

use App\Models\FollowupTool;
use Illuminate\Database\Seeder;

class FollowupToolsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        FollowupTool::truncate();
        FollowupTool::create(['id' => 1, 'name' => '电话回访']);
        FollowupTool::create(['id' => 2, 'name' => '短信回访']);
        FollowupTool::create(['id' => 3, 'name' => 'QQ回访']);
        FollowupTool::create(['id' => 4, 'name' => '微信回访']);
    }
}
