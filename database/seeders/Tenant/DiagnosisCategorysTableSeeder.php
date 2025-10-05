<?php

namespace Database\Seeders\Tenant;

use App\Models\DiagnosisCategory;
use Illuminate\Database\Seeder;

class DiagnosisCategorysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DiagnosisCategory::truncate();

        $root = DiagnosisCategory::create(['name' => '诊断分类', 'parentid' => 0]);

        DiagnosisCategory::create(['parentid' => $root->id,'name' => '常用ICD诊断']);
        DiagnosisCategory::create(['parentid' => $root->id,'name' => '自定义诊断']);
    }
}
