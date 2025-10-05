<?php

namespace Database\Seeders\Tenant;

use App\Models\PrescriptionFrequency;
use Illuminate\Database\Seeder;

class PrescriptionFrequencysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        PrescriptionFrequency::truncate();
        PrescriptionFrequency::create(['id' => 1, 'name' => '每日一次']);
        PrescriptionFrequency::create(['id' => 2, 'name' => '每日两次']);
        PrescriptionFrequency::create(['id' => 3, 'name' => '每日三次']);
        PrescriptionFrequency::create(['id' => 4, 'name' => '每日四次']);
    }
}
