<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SceneFieldTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [];

        // 自动加载 SceneFields 目录下的所有配置
        $configFiles = glob(__DIR__ . '/SceneFields/*Seeder.php');

        foreach ($configFiles as $file) {
            $className = 'Database\\Seeders\\Tenant\\SceneFields\\' . pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($className) && is_subclass_of($className, 'Database\\Seeders\\Tenant\\SceneFields\\BaseSceneFieldSeeder')) {
                $seeder = new $className();
                $fields = array_merge($fields, $seeder->getConfig());
            }
        }

        // 添加搜索字段
        foreach ($fields as &$field) {
            $field['api']              = $field['api'] ?? null;
            $field['keyword']          = implode(',', parse_pinyin($field['name']));
            $field['query_config']     = $field['query_config'] ?? null;
            $field['component_params'] = $field['component_params'] ?? null;
        }

        DB::table('scene_fields')->truncate();
        DB::table('scene_fields')->insert($fields);
    }
}
