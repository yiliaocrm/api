<?php

namespace Database\Seeders\Tenant;

use App\Models\FollowupType;
use Illuminate\Database\Seeder;

class FollowupTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        FollowupType::truncate();
        FollowupType::create(['id' => 1, 'name' => '未上门回访']);
        FollowupType::create(['id' => 2, 'name' => '未成交回访']);
        FollowupType::create(['id' => 3, 'name' => '术前回访']);
        FollowupType::create(['id' => 4, 'name' => '术后回访']);
        FollowupType::create(['id' => 5, 'name' => '满意度回访']);
        FollowupType::create(['id' => 6, 'name' => '活动回访']);
    }
}
