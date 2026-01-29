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

        $templates = [
            [
                'title' => '顾客基础信息',
                'template' => 'public/template/customer.xlsx',
                'use_import' => 'CustomerImport',
                'chunk_size' => 1000,
                'async_limit' => 1000,
                'create_user_id' => 1,
            ],
            [
                'title' => '媒介来源',
                'template' => 'public/template/medium.xlsx',
                'use_import' => 'MediumImport',
                'chunk_size' => 1000,
                'async_limit' => 1000,
                'create_user_id' => 1,
            ],
        ];

        foreach ($templates as $template) {
            ImportTemplate::query()->create($template);
        }
    }
}
