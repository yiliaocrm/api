<?php

namespace Database\Seeders\Tenant;

use App\Models\SmsCategory;
use Illuminate\Database\Seeder;

class SmsCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsCategory::query()->truncate();
        $categories = [
            ['name' => '系统通知', 'sort' => 1],
        ];
        foreach ($categories as $category) {
            SmsCategory::query()->create($category);
        }
    }
}
