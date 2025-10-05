<?php

namespace Database\Seeders\Tenant;

use App\Models\GoodsType;
use Illuminate\Database\Seeder;

class GoodsTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        GoodsType::truncate();

        $root  = GoodsType::create(['name' => '所有分类', 'parentid' => 0, 'type' => 'root', 'deleteable' => 0, 'editable' => 0]);
        $drug  = GoodsType::create(['name' => '药品分类', 'parentid' => $root->id, 'type' => 'drug', 'deleteable' => 0, 'editable' => 0]);
        $goods = GoodsType::create(['name' => '物品分类', 'parentid' => $root->id, 'type' => 'goods', 'deleteable' => 0, 'editable' => 0]);

        GoodsType::create(['name' => '化学药品和生物制品', 'parentid' => $drug->id, 'type' => 'drug', 'deleteable' => 0]);
        GoodsType::create(['name' => '中成药', 'parentid' => $drug->id, 'type' => 'drug','deleteable' => 0]);
        GoodsType::create(['name' => '中药饮片', 'parentid' => $drug->id, 'type' => 'drug','deleteable' => 0]);
    }
}
