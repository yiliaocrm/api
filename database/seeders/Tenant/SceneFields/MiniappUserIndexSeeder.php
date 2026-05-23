<?php

namespace Database\Seeders\Tenant\SceneFields;

class MiniappUserIndexSeeder extends BaseSceneFieldSeeder
{
    /**
     * 小程序用户管理场景化搜索字段。
     */
    public function getConfig(): array
    {
        return [
            [
                'page' => 'MiniappUserIndex',
                'name' => '小程序昵称',
                'table' => 'customer_wechats',
                'field' => 'nickname',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null'],
                ]),
            ],
            [
                'page' => 'MiniappUserIndex',
                'name' => '绑定号码',
                'table' => 'customer_wechats',
                'field' => 'phone',
                'field_type' => 'varchar',
                'component' => 'input',
                'operators' => json_encode([
                    ['text' => '包含', 'value' => 'like'],
                    ['text' => '等于', 'value' => '='],
                    ['text' => '不等于', 'value' => '<>'],
                    ['text' => '为空', 'value' => 'is null'],
                    ['text' => '不为空', 'value' => 'is not null'],
                ]),
            ],
        ];
    }
}
