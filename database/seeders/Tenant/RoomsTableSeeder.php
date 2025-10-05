<?php

namespace Database\Seeders\Tenant;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Room::query()->truncate();
        Room::query()->create(['store_id' => 1, 'name' => '1号', 'department_id' => 3, 'status' => 1]);
        Room::query()->create(['store_id' => 1, 'name' => '2号', 'department_id' => 3, 'status' => 1]);
    }
}
