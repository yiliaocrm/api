<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Sentinel;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $role = Sentinel::findRoleBySlug('administrators');
        $user = Sentinel::registerAndActivate([
            'email'         => 'admin',
            'password'      => 'admin',
            'name'          => '系统管理员',
            'remark'        => '系统默认管理员,请勿删除!',
            'department_id' => 1,
            'scheduleable'  => false
        ]);
        $role->users()->attach($user);
    }
}
