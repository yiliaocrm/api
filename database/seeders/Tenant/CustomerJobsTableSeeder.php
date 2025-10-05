<?php

namespace Database\Seeders\Tenant;

use App\Models\CustomerJob;
use Illuminate\Database\Seeder;

class CustomerJobsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CustomerJob::truncate();
        CustomerJob::create(['id' => 1, 'name' => '学生',   'remark' => '系统自带']);
        CustomerJob::create(['id' => 2, 'name' => '教师',   'remark' => '系统自带']);
        CustomerJob::create(['id' => 3, 'name' => '工程师', 'remark' => '系统自带']);
        CustomerJob::create(['id' => 4, 'name' => '私营业主', 'remark' => '系统自带']);
        CustomerJob::create(['id' => 5, 'name' => '自由职业', 'remark' => '系统自带']);
        CustomerJob::create(['id' => 6, 'name' => '其他', 'remark' => '系统自带']);
    }
}
