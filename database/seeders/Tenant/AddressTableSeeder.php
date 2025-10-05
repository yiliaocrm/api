<?php

namespace Database\Seeders\Tenant;

use App\Models\Address;
use Illuminate\Database\Seeder;

class AddressTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Address::truncate();

        $lists = [
            '北京',
            '天津',
            '河北',
            '山西',
            '内蒙古',
            '辽宁',
            '吉林',
            '黑龙江',
            '上海',
            '江苏',
            '浙江',
            '安徽',
            '福建',
            '江西',
            '山东',
            '河南',
            '湖北',
            '湖南',
            '广东',
            '广西',
            '海南',
            '重庆',
            '四川',
            '贵州',
            '云南',
            '西藏',
            '陕西',
            '甘肃',
            '青海',
            '宁夏',
            '新疆',
            '台湾',
            '香港',
            '澳门',
            '海外'
        ];

        foreach ($lists as $key => $value) {
            Address::create([
                'name'     => $value,
                'parentid' => 0
            ]);
        }
    }
}
