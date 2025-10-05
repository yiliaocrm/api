<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\CustomerPhoneRelationship;

class CustomerPhoneRelationshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomerPhoneRelationship::truncate();
        $relationships = [
            ['name' => '本人', 'system' => true],
            ['name' => '副号', 'system' => true],
            ['name' => '配偶', 'system' => false],
            ['name' => '父亲', 'system' => false],
            ['name' => '母亲', 'system' => false],
            ['name' => '子女', 'system' => false],
            ['name' => '兄弟', 'system' => false],
            ['name' => '姐妹', 'system' => false],
            ['name' => '其他', 'system' => false],
        ];
        foreach ($relationships as $relationship) {
            CustomerPhoneRelationship::create($relationship);
        }
    }
}
