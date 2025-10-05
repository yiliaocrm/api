<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $now = now();
        DB::table('accounts')->truncate();
        DB::table('accounts')->insert([
            ['id' => 1, 'name' => '余额支付', 'remark' => '系统保留,无法操作!', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => '现金支付', 'remark' => '系统保留,无法操作!', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => '微信', 'remark' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => '支付宝', 'remark' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => '刷卡', 'remark' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
