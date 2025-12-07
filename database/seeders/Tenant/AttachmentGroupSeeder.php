<?php

namespace Database\Seeders\Tenant;

use App\Models\AttachmentGroup;
use Illuminate\Database\Seeder;

class AttachmentGroupSeeder extends Seeder
{
    /**
     * 初始化附件分组
     */
    public function run(): void
    {
        // 清空表数据
        AttachmentGroup::query()->truncate();

        $groups = [
            [
                'id'        => 1,
                'name'      => '全部',
                'parent_id' => 0,
                'order'     => 0,
                'system'    => 1,
            ],
            [
                'id'        => 2,
                'name'      => '未分组',
                'parent_id' => 0,
                'order'     => 1,
                'system'    => 1,
            ],
            [
                'id'        => 3,
                'name'      => '我的图片',
                'parent_id' => 0,
                'order'     => 2,
                'system'    => 1,
            ],
            [
                'id'        => 4,
                'name'      => '我的文件',
                'parent_id' => 0,
                'order'     => 3,
                'system'    => 1,
            ],
        ];

        foreach ($groups as $group) {
            AttachmentGroup::query()->create($group);
        }
    }
}
