<?php

namespace Database\Seeders\Tenant;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Position::query()->truncate();
        $positions = [
            [
                'name' => '前台',
                'code' => 'receptionist',
            ],
            [
                'name' => '销售顾问',
                'code' => 'consultant',
            ],
            [
                'name' => '医生',
                'code' => 'doctor',
            ],
            [
                'name' => '护士',
                'code' => 'nurse',
            ],
            [
                'name' => '技师',
                'code' => 'technician',
            ],
            [
                'name' => '采购',
                'code' => 'purchaser',
            ],
            [
                'name' => '库管',
                'code' => 'storekeeper',
            ],
            [
                'name' => '财务',
                'code' => 'accountant',
            ],
            [
                'name' => '药房',
                'code' => 'pharmacist',
            ],
            [
                'name' => '网电咨询',
                'code' => 'internet_service',
            ],
            [
                'name' => '客服',
                'code' => 'customer_service',
            ],
            [
                'name' => '行政人员',
                'code' => 'administrative',
            ],
            [
                'name' => '管理员',
                'code' => 'administrator',
            ]
        ];
        foreach ($positions as $position) {
            Position::query()->create($position);
        }
    }
}
