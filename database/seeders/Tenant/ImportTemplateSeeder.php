<?php

namespace Database\Seeders\Tenant;

use App\Models\ImportTemplate;
use Illuminate\Database\Seeder;

class ImportTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ImportTemplate::query()->truncate();
    }
}
