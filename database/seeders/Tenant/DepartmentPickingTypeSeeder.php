<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentPickingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('department_picking_types')->truncate();
        DB::table('department_picking_types')->insert([
            'id'         => 1,
            'name'       => '常规领料',
            'keyword'    => '常规领料,cgll,changguilingliao',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
