<?php

namespace Database\Seeders\Tenant;

use App\Models\Bed;
use Illuminate\Database\Seeder;

class BedsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Bed::query()->truncate();
        Bed::query()->create(['store_id' => 1, 'name' => '201床', 'status' => 1]);
        Bed::query()->create(['store_id' => 1, 'name' => '202床', 'status' => 1]);
    }
}
