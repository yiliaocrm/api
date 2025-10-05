<?php

namespace Database\Seeders\Tenant;

use App\Models\Medium;
use Illuminate\Database\Seeder;

class MediumsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Medium::query()->truncate();

        Medium::query()->create([
            'id'             => 2,
            'name'           => '员工推荐',
            'parentid'       => 0,
            'create_user_id' => 1,
            'created_at'     => now(),
            'updated_at'     => now()
        ]);

        Medium::query()->create([
            'id'             => 3,
            'name'           => '客户推荐',
            'parentid'       => 0,
            'create_user_id' => 1,
            'created_at'     => now(),
            'updated_at'     => now()
        ]);

        Medium::query()->create([
            'id'             => 4,
            'name'           => '市场渠道',
            'parentid'       => 0,
            'create_user_id' => 1,
            'created_at'     => now(),
            'updated_at'     => now()
        ]);
        Medium::query()->create([
            'id'             => 10,
            'name'           => '网络',
            'parentid'       => 0,
            'create_user_id' => 1,
            'created_at'     => now(),
            'updated_at'     => now()
        ]);

        Medium::query()->create([
            'name'           => '线下',
            'parentid'       => 0,
            'create_user_id' => 1,
            'created_at'     => now(),
            'updated_at'     => now()
        ]);
    }
}
