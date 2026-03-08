<?php

namespace Database\Seeders\Tenant;

use App\Models\CustomerPhotoType;
use Illuminate\Database\Seeder;

class CustomerPhotoTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomerPhotoType::truncate();
        CustomerPhotoType::query()->create(['id' => 1, 'name' => '术前',   'remark' => '系统自带']);
        CustomerPhotoType::query()->create(['id' => 2, 'name' => '术后',   'remark' => '系统自带']);
        CustomerPhotoType::query()->create(['id' => 3, 'name' => '恢复',   'remark' => '系统自带']);
    }
}
