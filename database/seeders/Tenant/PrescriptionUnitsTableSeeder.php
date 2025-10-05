<?php

namespace Database\Seeders\Tenant;

use App\Models\PrescriptionUnit;
use Illuminate\Database\Seeder;

class PrescriptionUnitsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        PrescriptionUnit::truncate();
        PrescriptionUnit::create(['id' => 1, 'name' => 'g']);
        PrescriptionUnit::create(['id' => 2, 'name' => 'mg']);
        PrescriptionUnit::create(['id' => 3, 'name' => 'ml']);
        PrescriptionUnit::create(['id' => 4, 'name' => '支']);
        PrescriptionUnit::create(['id' => 5, 'name' => '袋']);
        PrescriptionUnit::create(['id' => 6, 'name' => '瓶']);
    }
}
