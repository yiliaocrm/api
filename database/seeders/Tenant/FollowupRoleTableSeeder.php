<?php

namespace Database\Seeders\Tenant;

use App\Models\FollowupRole;
use Illuminate\Database\Seeder;

class FollowupRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        FollowupRole::query()->truncate();
        FollowupRole::query()->create(['name' => '归属开发', 'value' => 'ascription']);
        FollowupRole::query()->create(['name' => '归属现场', 'value' => 'consultant']);
        FollowupRole::query()->create(['name' => '归属客服', 'value' => 'service']);
        FollowupRole::query()->create(['name' => '执行医助', 'value' => 'assistant']);
        FollowupRole::query()->create(['name' => '主治医生', 'value' => 'doctor']);
        FollowupRole::query()->create(['name' => '美容师', 'value' => 'beautician']);
        FollowupRole::query()->create(['name' => '护士', 'value' => 'nurse']);
        FollowupRole::query()->create(['name' => '麻醉师', 'value' => 'anesthetist']);
    }
}
