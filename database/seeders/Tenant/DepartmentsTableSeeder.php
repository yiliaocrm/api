<?php

namespace Database\Seeders\Tenant;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Department::query()->truncate();
        Department::query()->create([
            'name' => '信息科', 'disabled' => 0, 'primary' => 0, 'remark' => '系统默认生成'
        ]);
        Department::query()->create([
            'name' => '财务科', 'disabled' => 0, 'primary' => 1, 'remark' => '系统默认生成'
        ]);
        Department::query()->create([
            'name' => '整形外科', 'disabled' => 0, 'primary' => 1
        ]);
        Department::query()->create([
            'name' => '皮肤科', 'disabled' => 0, 'primary' => 1
        ]);
        Department::query()->create([
            'name' => '微整形', 'disabled' => 0, 'primary' => 1
        ]);
        Department::query()->create([
            'name' => '口腔科', 'disabled' => 0, 'primary' => 1
        ]);
        Department::query()->create([
            'name' => '前台', 'disabled' => 0, 'primary' => 0
        ]);
    }
}
