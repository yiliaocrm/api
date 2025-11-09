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
        FollowupType::query()->truncate();
        FollowupType::query()->create([
            'id'   => 1,
            'name' => '未上门回访',
            'icon' => 'el-icon-warning'
        ]);
        FollowupType::query()->create([
            'id'   => 2,
            'name' => '未成交回访',
            'icon' => 'el-icon-circle-close'
        ]);
        FollowupType::query()->create([
            'id'   => 3,
            'name' => '术前回访',
            'icon' => 'el-icon-first-aid-kit'
        ]);
        FollowupType::query()->create([
            'id'   => 4,
            'name' => '术后回访',
            'icon' => 'el-icon-medal'
        ]);
        FollowupType::query()->create([
            'id'   => 5,
            'name' => '满意度回访',
            'icon' => 'el-icon-star'
        ]);
        FollowupType::query()->create([
            'id'   => 6,
            'name' => '活动回访',
            'icon' => 'el-icon-present'
        ]);
    }
}
