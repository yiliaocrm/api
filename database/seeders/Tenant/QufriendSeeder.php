<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QufriendSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('qufriends')->truncate();
        $now   = now();
        $data  = [];
        $names = ['父母', '子女', '配偶', '兄弟姐妹', '亲戚', '朋友', '同事', '邻居', '陪同人', '其他'];
        foreach ($names as $name) {
            $data[] = ['name' => $name, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('qufriends')->insert($data);
    }
}
