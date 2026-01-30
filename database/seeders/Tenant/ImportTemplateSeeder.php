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
                'icon' => 'el-icon-user-filled',
                'title' => '顾客信息',
                'template' => 'template/customer.xlsx',
                'use_import' => 'CustomerImport',
                'chunk_size' => 1000,
                'async_limit' => 1000,
                'create_user_id' => 1,
            ],
            [
                'icon' => 'el-icon-connection',
                'title' => '媒介来源',
                'template' => 'template/medium.xlsx',
                'use_import' => 'MediumImport',
                'chunk_size' => 1000,
                'async_limit' => 1000,
                'create_user_id' => 1,
            ],
            [
                'icon' => 'el-icon-location',
                'title' => '地区信息',
                'template' => 'template/address.xlsx',
                'use_import' => 'AddressImport',
                'chunk_size' => 1000,
                'async_limit' => 1000,
                'create_user_id' => 1,
            ],
            [
                'icon' => 'el-icon-collection-tag',
                'title' => '收费项目分类',
                'template' => 'template/product_type.xlsx',
                'use_import' => 'ProductTypeImport',
                'chunk_size' => 1000,
                'async_limit' => 1000,
                'create_user_id' => 1,
            ],
            [
                'icon' => 'el-icon-goods',
                'title' => '收费项目',
                'template' => 'template/product.xlsx',
                'use_import' => 'ProductImport',
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
