<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('unit')->truncate();
        $units  = [
            '个',
            '瓶',
            '盒',
            '件',
            '箱',
            '包',
            '支',
            '片',
            '套',
            '袋',
            '对',
            '台',
            '张',
            '辆',
            '盏',
            '把',
            '桶',
            '本',
            '米',
            '付',
            '部',
            '斤',
            '米',
            '克',
            '吨',
            '册',
            '根',
            '副',
            '条',
            '份',
            '卷',
            '只',
            '块',
            '贴',
            '筒',
            '盘',
            '板',
            '枚',
            '粒',
            '颗',
            '双',
        ];
        $values = [];
        foreach ($units as $unit) {
            $values[] = [
                'name'       => $unit,
                'keyword'    => implode(',', parse_pinyin($unit)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('unit')->insert($values);
    }
}
