<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;

class AdminMenusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('admin_menus')->delete();

        \DB::table('admin_menus')->insert(array (
            0 =>
            array (
                'id' => 1,
                'parentid' => 0,
                'name' => 'Platform',
                'title' => '系统',
                'path' => '/platform',
                'meta' => '{"title":"\\u7cfb\\u7edf","icon":"el-icon-setting","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => NULL,
                'child' => 1,
                'permission' => '',
                'order' => 3,
                'keyword' => 'xitong,xt,系统',
                'remark' => NULL,
                'tree' => '0-1',
                'created_at' => '2024-05-22 16:15:40',
                'updated_at' => '2025-05-04 16:59:00',
            ),
            1 =>
            array (
                'id' => 2,
                'parentid' => 1,
                'name' => 'AdminMenuIndex',
                'title' => '后台菜单',
                'path' => '/platform/menu',
                'meta' => '{"title":"\\u540e\\u53f0\\u83dc\\u5355","icon":"el-icon-grid","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => 'admin-menu/index',
                'child' => 0,
                'permission' => '',
                'order' => 1,
                'keyword' => 'houtaicaidan,htcd,后台菜单',
                'remark' => NULL,
                'tree' => '0-2',
                'created_at' => '2024-05-22 19:25:25',
                'updated_at' => '2025-05-04 16:58:06',
            ),
            2 =>
            array (
                'id' => 3,
                'parentid' => 0,
                'name' => 'Tenant',
                'title' => '租户',
                'path' => '/tenant',
                'meta' => '{"title":"\\u79df\\u6237","icon":"el-icon-home-filled","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => NULL,
                'child' => 1,
                'permission' => '',
                'order' => 2,
                'keyword' => 'zuhu,zh,租户',
                'remark' => NULL,
                'tree' => '0-3',
                'created_at' => '2024-05-22 19:30:17',
                'updated_at' => '2024-05-22 19:30:29',
            ),
            3 =>
            array (
                'id' => 4,
                'parentid' => 3,
                'name' => 'TenantIndex',
                'title' => '租户管理',
                'path' => '/tenant/index',
                'meta' => '{"title":"\\u79df\\u6237\\u7ba1\\u7406","icon":"el-icon-avatar","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => 'tenant/index',
                'child' => 0,
                'permission' => '',
                'order' => 2,
                'keyword' => 'zuhuguanli,zhgl,租户管理',
                'remark' => NULL,
                'tree' => '0-3-4',
                'created_at' => '2024-05-22 19:31:44',
                'updated_at' => '2025-05-05 10:39:24',
            ),
            4 =>
            array (
                'id' => 5,
                'parentid' => 3,
                'name' => 'MenuIndex',
                'title' => '菜单配置',
                'path' => '/tenant/menu',
                'meta' => '{"title":"\\u83dc\\u5355\\u914d\\u7f6e","icon":"el-icon-menu","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => 'menu/index',
                'child' => 0,
                'permission' => '',
                'order' => 3,
                'keyword' => 'caidanpeizhi,cdpz,菜单配置',
                'remark' => NULL,
                'tree' => '0-3-5',
                'created_at' => '2024-05-22 19:33:30',
                'updated_at' => '2025-05-05 10:39:30',
            ),
            5 =>
            array (
                'id' => 6,
                'parentid' => 0,
                'name' => 'Home',
                'title' => '首页',
                'path' => '/home',
                'meta' => '{"title":"\\u9996\\u9875","icon":"el-icon-home-filled","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => NULL,
                'child' => 1,
                'permission' => '',
                'order' => 1,
                'keyword' => 'shouye,sy,首页',
                'remark' => NULL,
                'tree' => '0-6',
                'created_at' => '2024-05-22 19:42:00',
                'updated_at' => '2024-05-22 19:42:00',
            ),
            6 =>
            array (
                'id' => 7,
                'parentid' => 6,
                'name' => 'DashboardIndex',
                'title' => '控制台',
                'path' => '/home/dashboard',
                'meta' => '{"title":"\\u63a7\\u5236\\u53f0","icon":"el-icon-menu","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => 'dashboard/index',
                'child' => 0,
                'permission' => '',
                'order' => 0,
                'keyword' => 'kongzhitai,kzt,控制台',
                'remark' => NULL,
                'tree' => '0-6-7',
                'created_at' => '2024-05-22 19:53:27',
                'updated_at' => '2025-09-18 01:05:49',
            ),
            7 =>
            array (
                'id' => 8,
                'parentid' => 6,
                'name' => 'ProfileIndex',
                'title' => '帐号信息',
                'path' => '/home/profile',
                'meta' => '{"title":"\\u5e10\\u53f7\\u4fe1\\u606f","icon":"el-icon-user","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => 'profile/index',
                'child' => 0,
                'permission' => '',
                'order' => 0,
                'keyword' => 'zhanghaoxinxi,zhxx,帐号信息',
                'remark' => NULL,
                'tree' => '0-6-8',
                'created_at' => '2024-05-22 19:54:15',
                'updated_at' => '2025-09-18 03:41:45',
            ),
            8 =>
            array (
                'id' => 10,
                'parentid' => 1,
                'name' => 'ConfigIndex',
                'title' => '系统设置',
                'path' => '/platform/config',
                'meta' => '{"title":"\\u7cfb\\u7edf\\u8bbe\\u7f6e","icon":"el-icon-setting","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => 'config/index',
                'child' => 0,
                'permission' => '',
                'order' => 2,
                'keyword' => 'xitongshezhi,xtsz,系统设置',
                'remark' => NULL,
                'tree' => '0-1-10',
                'created_at' => '2025-05-04 17:28:33',
                'updated_at' => '2025-05-04 17:28:33',
            ),
            9 =>
            array (
                'id' => 11,
                'parentid' => 3,
                'name' => 'PlanIndex',
                'title' => '订阅方案',
                'path' => '/tenant/plan',
                'meta' => '{"title":"\\u8ba2\\u9605\\u65b9\\u6848","icon":"el-icon-shopping-bag","active":null,"color":null,"type":"menu","fullpage":false,"tag":null}',
                'component' => 'plan/index',
                'child' => 0,
                'permission' => '',
                'order' => 1,
                'keyword' => 'dingyuefangan,dyfa,订阅方案',
                'remark' => '设置租户可用功能',
                'tree' => '0-3-11',
                'created_at' => '2025-05-05 10:38:48',
                'updated_at' => '2025-05-05 10:39:19',
            ),
        ));


    }
}
