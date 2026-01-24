<?php

namespace Database\Seeders\Tenant;

use App\Models\WebMenu;
use Illuminate\Database\Seeder;

class WebMenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        WebMenu::query()->truncate();

        $this->createWorkbenchMenu();
        $this->createMemberCenterMenu();
        $this->createCashierMenu();
        $this->createTreatmentMenu();
        $this->createAppointmentMenu();
        $this->createErpMenu();
        $this->createMarketingMenu();
        $this->createReportMenu();
        $this->createDictionaryMenu();
        $this->createSystemSettingMenu();

        $this->createAppMenu();
    }

    public function createWorkbenchMenu()
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '工作台',
            'icon'     => 'iconfont if-desktop'
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '今日工作',
            'path'       => '/workbench/today',
            'component'  => '',
            'url'        => '/new#/workbench/today',
            'meta'       => [
                'title' => '今日工作'
            ],
            'icon'       => 'iconfont if-home',
            'route'      => 'WorkbenchToday',
            'remark'     => '工作台数据汇总',
            'permission' => 'workbench.today'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看今日工作数据',
            'display'    => false,
            'permission' => 'workbench.today.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '到店',
            'remark'     => '顾客预约到店操作',
            'display'    => false,
            'permission' => 'workbench.today.arrival',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '挂号',
            'remark'     => '分诊挂号',
            'display'    => false,
            'permission' => 'workbench.today.reception',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '预约管理',
            'path'       => '/workbench/appointment',
            'component'  => '',
            'url'        => '/new#/workbench/appointment',
            'meta'       => [
                'title' => '预约管理'
            ],
            'icon'       => 'iconfont if-appointment',
            'route'      => 'WorkbenchAppointment',
            'remark'     => '预约管理',
            'permission' => 'workbench.appointment'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看预约记录',
            'display'    => false,
            'permission' => 'workbench.appointment.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '新增',
            'remark'     => '添加预约',
            'display'    => false,
            'permission' => 'workbench.appointment.create',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '编辑',
            'remark'     => '更新预约记录',
            'display'    => false,
            'permission' => 'workbench.appointment.update',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '删除',
            'remark'     => '删除预约记录',
            'display'    => false,
            'permission' => 'workbench.appointment.remove',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '导出',
            'remark'     => '导出预约记录',
            'display'    => false,
            'permission' => 'workbench.appointment.export',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '分诊接待',
            'path'       => '/workbench/reception',
            'component'  => '',
            'url'        => '/new#/workbench/reception',
            'meta'       => [
                'title' => '分诊接待'
            ],
            'icon'       => 'iconfont if-reception',
            'route'      => 'WorkbenchReception',
            'remark'     => '前台接待',
            'permission' => 'workbench.reception'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看记录',
            'remark'     => '查看分诊接待记录',
            'display'    => false,
            'permission' => 'workbench.reception.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '分诊挂号',
            'remark'     => '新增分诊挂号操作',
            'display'    => false,
            'permission' => 'workbench.reception.create',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '修改分诊',
            'remark'     => '修改分诊挂号记录',
            'display'    => false,
            'permission' => 'workbench.reception.update',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '删除分诊',
            'remark'     => '删除分诊(已接诊的无法操作)',
            'display'    => false,
            'permission' => 'workbench.reception.remove',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '历史记录',
            'remark'     => '查看历史分诊记录(默认只能看当天)',
            'display'    => false,
            'permission' => 'workbench.reception.history',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '改派咨询',
            'remark'     => '重新分配顾问',
            'display'    => false,
            'permission' => 'workbench.reception.dispatch.consultant',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '改派医生',
            'remark'     => '重新分配医生',
            'display'    => false,
            'permission' => 'workbench.reception.dispatch.doctor',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '回访管理',
            'path'       => '/workbench/followup',
            'component'  => '',
            'url'        => '/new#/workbench/followup',
            'meta'       => [
                'title' => '分诊接待'
            ],
            'icon'       => 'iconfont if-followup',
            'route'      => 'WorkbenchFollowup',
            'remark'     => '回访管理',
            'permission' => 'workbench.followup'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看回访记录',
            'display'    => false,
            'permission' => 'workbench.followup.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '回访',
            'remark'     => '执行回访',
            'display'    => false,
            'permission' => 'workbench.followup.execute',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '更新',
            'remark'     => '更新回访记录',
            'display'    => false,
            'permission' => 'workbench.followup.update',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '删除',
            'remark'     => '删除回访记录',
            'display'    => false,
            'permission' => 'workbench.followup.remove',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '生日提醒',
            'path'       => '/workbench/birthday',
            'component'  => '',
            'url'        => '/new#/workbench/birthday',
            'meta'       => [
                'title' => '生日提醒'
            ],
            'icon'       => 'iconfont if-birthday',
            'route'      => 'WorkbenchBirthday',
            'remark'     => '生日提醒',
            'permission' => 'workbench.birthday'
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '库存预警',
            'path'       => '/workbench/alarm',
            'component'  => '',
            'url'        => '/new#/workbench/alarm',
            'meta'       => [
                'title' => '库存预警'
            ],
            'icon'       => 'iconfont if-inventory-alarm',
            'route'      => 'WorkbenchAlarm',
            'remark'     => '库存上下限预警',
            'permission' => 'workbench.alarm'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看库存预警数据',
            'display'    => false,
            'permission' => 'workbench.alarm.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '导出',
            'remark'     => '导出库存预警数据',
            'display'    => false,
            'permission' => 'workbench.alarm.export',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '过期预警',
            'path'       => '/workbench/expiry',
            'component'  => '',
            'url'        => '/new#/workbench/expiry',
            'meta'       => [
                'title' => '过期预警'
            ],
            'icon'       => 'iconfont if-inventory-expiry',
            'route'      => 'WorkbenchExpiry',
            'remark'     => '库存上下限预警',
            'permission' => 'workbench.expiry'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看库存过期预警',
            'display'    => false,
            'permission' => 'workbench.expiry.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '导出',
            'remark'     => '导出库存过期提醒',
            'display'    => false,
            'permission' => 'workbench.expiry.export',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '网电报单',
            'icon'       => 'iconfont if-reservation',
            'route'      => 'ReservationIndex',
            'path'       => '/workbench/reservation',
            'component'  => 'reservation/index',
            'meta'       => [
                'title' => '网电报单'
            ],
            'remark'     => '适用于各个渠道口,客户报备。',
            'permission' => 'reservation.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '登记咨询',
            'icon'       => 'icon-add',
            'remark'     => '创建报单记录',
            'display'    => false,
            'permission' => 'reservation.create',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '修改咨询',
            'remark'     => '允许修改咨询记录。',
            'display'    => false,
            'permission' => 'reservation.update',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '删除咨询',
            'icon'       => 'icon-cancel',
            'remark'     => '允许删除咨询记录。',
            'display'    => false,
            'permission' => 'reservation.remove',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '到院查询',
            'icon'       => 'iconfont if-history',
            'route'      => 'ReservationReception',
            'remark'     => '查询已上门顾客信息',
            'display'    => false,
            'permission' => 'reservation.reception',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '现场设计',
            'path'       => '/workbench/consultant',
            'component'  => 'consultant/index',
            'meta'       => [
                'title' => '现场设计'
            ],
            'icon'       => 'iconfont if-consultant',
            'route'      => 'ConsultantIndex',
            'remark'     => '现场咨询记录',
            'permission' => 'consultant.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '历史记录',
            'icon'       => 'icon-history',
            'remark'     => '查看历史咨询记录(默认只能看当天)',
            'display'    => false,
            'permission' => 'consultant.history'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '取消接待',
            'icon'       => 'icon-cancel',
            'display'    => false,
            'permission' => 'consultant.cancel.reception',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '报价手册',
            'path'       => '/workbench/quotation',
            'component'  => 'quotation/index',
            'meta'       => [
                'title' => '报价手册'
            ],
            'icon'       => 'iconfont if-quotation',
            'route'      => 'QuotationIndex',
            'remark'     => '查看:项目、套餐、物品销售价格',
            'permission' => 'consultant.quotation',
        ]);
    }

    public function createDictionaryMenu()
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '字典维护',
            'icon'     => 'iconfont if-dictionary'
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '基础信息',
            'icon'     => ''
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '支付方式',
            'url'        => '/new#/dict/base/account',
            'path'       => '/dict/accounts',
            'meta'       => [
                'title' => '支付方式'
            ],
            'route'      => 'AccountsIndex',
            'permission' => 'accounts.manage',
            'icon'       => 'iconfont if-credit-card'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '费用类别',
            'url'        => '/new#/dict/base/expense-category',
            'path'       => '/dict/expense-category',
            'meta'       => [
                'title' => '费用类别'
            ],
            'icon'       => 'iconfont if-economic',
            'route'      => 'ExpenseCategoryIndex',
            'permission' => 'expense.category.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '收费项目',
            'path'       => '/dict/product',
            'component'  => 'product/index',
            'meta'       => [
                'title' => '收费项目'
            ],
            'url'        => '/new#/dict/base/product',
            'icon'       => 'iconfont if-product',
            'route'      => 'ProductIndex',
            'permission' => 'product.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '项目套餐',
            'url'        => '/new#/dict/base/product-package',
            'path'       => '/dict/product-package',
            'component'  => 'product-package/index',
            'meta'       => [
                'title' => '项目套餐'
            ],
            'icon'       => 'iconfont if-template',
            'route'      => 'ProductPackageManage',
            'remark'     => '收费项目套餐',
            'permission' => 'product.package.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '咨询项目',
            'path'       => '/dict/item',
            'url'        => '/new#/dict/base/item',
            'component'  => 'item/index',
            'meta'       => [
                'title' => '咨询项目'
            ],
            'icon'       => 'iconfont if-item',
            'route'      => 'ItemIndex',
            'permission' => 'item.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '打印模板',
            'url'        => '/new#/dict/base/print-template',
            'path'       => '/dict/print-template',
            'component'  => 'print-template/index',
            'meta'       => [
                'title' => '打印模板'
            ],
            'icon'       => 'iconfont if-printer',
            'route'      => 'PrintTemplateIndex',
            'permission' => 'print.template.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '未成交原因',
            'url'        => '/new#/dict/base/failure',
            'path'       => '/dict/failure',
            'meta'       => [
                'title' => '未成交原因'
            ],
            'icon'       => 'iconfont if-failure',
            'route'      => 'FailureIndex',
            'permission' => 'failure.manage',
        ]);

        // 顾客
        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '顾客字典',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '职业管理',
            'url'        => '/new#/dict/customer/job',
            'path'       => '/dict/customer-job',
            'meta'       => [
                'title' => '职业管理'
            ],
            'icon'       => 'iconfont if-professional',
            'route'      => 'CustomerJobIndex',
            'remark'     => '管理顾客职业信息',
            'permission' => 'customer.job.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '经济能力',
            'url'        => '/new#/dict/customer/economic',
            'path'       => '/dict/customer-economic',
            'meta'       => [
                'title' => '经济能力'
            ],
            'icon'       => 'iconfont if-economic',
            'route'      => 'CustomerEconomicIndex',
            'permission' => 'customer.economic.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '媒介来源',
            'url'        => '/new#/dict/medium',
            'path'       => '/dict/medium',
            'meta'       => [
                'title' => '媒介来源'
            ],
            'icon'       => 'iconfont if-medium',
            'route'      => 'MediumIndex',
            'permission' => 'medium.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '地区信息',
            'url'        => '/new#/dict/customer/address',
            'path'       => '/dict/address',
            'meta'       => [
                'title' => '地区信息'
            ],
            'icon'       => 'iconfont if-map',
            'route'      => 'AddressIndex',
            'permission' => 'address.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '标签管理',
            'url'        => '/new#/dict/tags',
            'path'       => '/dict/tags',
            'meta'       => [
                'title' => '标签管理'
            ],
            'icon'       => 'iconfont if-tag',
            'route'      => 'TagsIndex',
            'permission' => 'tags.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '亲友关系',
            'url'        => '/new#/dict/customer/qufriend',
            'path'       => '/dict/qufriend',
            'meta'       => [
                'title' => '亲友关系'
            ],
            'route'      => 'QufriendIndex',
            'permission' => 'qufriend.manage',
            'icon'       => 'iconfont if-credit-card'
        ]);

        // 网电字典
        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '网电字典',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '受理类型',
            'url'        => '/new#/dict/reservation/type',
            'path'       => '/dict/reservation-type',
            'icon'       => 'iconfont if-setting',
            'meta'       => [
                'title' => '受理类型'
            ],
            'component'  => 'reservation-type/index',
            'route'      => 'ReservationTypeIndex',
            'permission' => 'reservation.type.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '咨询模板',
            'url'        => '/new#/dict/reservation/template',
            'icon'       => 'iconfont if-template',
            'path'       => '/dict/reservation-remark',
            'route'      => 'ReservationRemarkIndex',
            'meta'       => [
                'title' => '咨询模板'
            ],
            'component'  => 'reservation-remark/index',
            'permission' => 'reservation.remark.manage',
        ]);

        // 回访字典
        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '回访字典',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '回访工具',
            'url'        => '/new#/dict/followup/tool',
            'icon'       => 'iconfont if-tool',
            'path'       => '/dict/followup-tool',
            'meta'       => [
                'title' => '回访工具'
            ],
            'route'      => 'FollowupToolIndex',
            'component'  => 'followup-tool/index',
            'permission' => 'followup.tool.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '回访类型',
            'url'        => '/new#/dict/followup/type',
            'icon'       => 'iconfont if-setting',
            'path'       => '/dict/followup-type',
            'meta'       => [
                'title' => '回访类型'
            ],
            'route'      => 'FollowupTypeIndex',
            'component'  => 'followup-type/index',
            'permission' => 'followup.type.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '回访角色',
            'url'        => '/new#/dict/followup/role',
            'icon'       => 'iconfont if-role',
            'path'       => '/dict/followup-role',
            'route'      => 'FollowupRoleIndex',
            'meta'       => [
                'title' => '回访角色'
            ],
            'component'  => 'followup-role/index',
            'permission' => 'followup.role.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '回访模板',
            'url'        => '/new#/dict/followup/template',
            'icon'       => 'iconfont if-template',
            'path'       => '/dict/followup-template',
            'meta'       => [
                'title' => '回访模板'
            ],
            'route'      => 'FollowupTemplateIndex',
            'component'  => 'followup-template/index',
            'permission' => 'followup.template.manage',
        ]);

        // 现场字典
        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '现场字典',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '咨询模板',
            'url'        => '/new#/dict/consultant/template',
            'icon'       => 'iconfont if-template',
            'path'       => '/dict/consultant-remark-template',
            'meta'       => [
                'title' => '咨询模板'
            ],
            'route'      => 'ConsultantRemarkTemplateIndex',
            'component'  => 'consultant-remark-template/index',
            'permission' => 'consultant.remark.manage',
        ]);
    }

    public function createReportMenu()
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '报表中心',
            'icon'     => 'iconfont if-barchart'
        ]);
        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '顾客报表'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '客户项目明细表',
            'icon'       => 'icon-star',
            'url'        => '/new#/report/customer/product',
            'path'       => '/report/customer-product',
            'route'      => 'ReportCustomerProduct',
            'meta'       => [
                'title' => '客户项目明细表'
            ],
            'component'  => 'report/customer-product',
            'permission' => 'report.customer.product',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '客户物品明细表',
            'icon'       => 'iconfont if-goods',
            'url'        => '/new#/report/customer/goods',
            'path'       => '/report/customer-goods',
            'route'      => 'ReportCustomerGoods',
            'meta'       => [
                'title' => '客户物品明细表'
            ],
            'component'  => 'report/customer-goods',
            'permission' => 'report.customer.goods',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '客户退款明细表',
            'icon'       => 'iconfont if-refund',
            'url'        => '/new#/report/customer/refund',
            'path'       => '/report/customer-refund',
            'meta'       => [
                'title' => '客户退款明细表'
            ],
            'route'      => 'ReportCustomerRefund',
            'component'  => 'report/customer-refund',
            'permission' => 'report.customer.refund',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '客户积分明细表',
            'icon'       => 'iconfont if-integral',
            'path'       => '/report/customer-integrals',
            'meta'       => [
                'title' => '客户积分明细表'
            ],
            'url'        => '/new#/report/customer/integrals',
            'route'      => 'ReportCustomerIntegrals',
            'component'  => 'report/customer-integrals',
            'permission' => 'integral.manage',
            'remark'     => '查询客户积分变动明细',
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '运营报表'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '咨询成功率分析表',
            'icon'       => 'icon-template',
            'path'       => '/report/reception-product-analysis',
            'meta'       => [
                'title' => '咨询成功率分析表'
            ],
            'route'      => 'ReportReceptionProductAnalysis',
            'component'  => 'report/reception-product-analysis',
            'permission' => 'report.reception.product.analysis',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '现场咨询明细表',
            'icon'       => 'iconfont if-consultant',
            'path'       => '/report/consultant-detail',
            'url'        => '/new#/report/operation/consultant-detail',
            'meta'       => [
                'title' => '现场咨询明细表'
            ],
            'route'      => 'ReportConsultantDetail',
            'component'  => 'report/consultant-detail',
            'permission' => 'report.consultant.detail'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '现场开单明细表',
            'icon'       => 'iconfont if-shopping-cart',
            'path'       => '/report/consultant-order',
            'url'        => '/new#/report/operation/consultant-order',
            'meta'       => [
                'title' => '现场开单明细表'
            ],
            'route'      => 'ReportConsultantOrder',
            'component'  => 'report/consultant-order',
            'permission' => 'report.consultant.order'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '二开零购明细表',
            'icon'       => 'iconfont if-shopping-cart',
            'path'       => '/report/erkai-detail',
            'url'        => '/new#/report/operation/erkai-detail',
            'route'      => 'ReportErkaiDetail',
            'meta'       => [
                'title' => '二开零购明细表'
            ],
            'component'  => 'report/erkai-detail',
            'permission' => 'report.erkai.detail'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '治疗划扣明细表',
            'icon'       => 'iconfont if-treatment',
            'path'       => '/report/treatment-detail',
            'url'        => '/new#/report/operation/treatment-detail',
            'meta'       => [
                'title' => '治疗划扣明细表'
            ],
            'route'      => 'ReportTreatmentDetail',
            'component'  => 'report/treatment-detail',
            'permission' => 'report.treatment.detail',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '科室营业汇总表',
            'icon'       => 'iconfont if-turnover',
            'path'       => '/report/department-cashier',
            'url'        => '/new#/report/operation/department-cashier',
            'meta'       => [
                'title' => '科室营业汇总表'
            ],
            'route'      => 'ReportDepartmentCashier',
            'component'  => 'report/department-cashier',
            'permission' => 'report.department.cashier',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '项目销售排行榜',
            'icon'       => 'iconfont if-ranking',
            'path'       => '/report/product-ranking',
            'url'        => '/new#/report/operation/product-ranking',
            'meta'       => [
                'title' => '项目销售排行榜'
            ],
            'route'      => 'ReportProductRanking',
            'component'  => 'report/product-ranking',
            'permission' => 'report.product.ranking'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '回访情况统计表',
            'icon'       => 'iconfont if-followup',
            'path'       => '/report/followup-statistics',
            'meta'       => [
                'title' => '回访情况统计表'
            ],
            'route'      => 'ReportFollowupStatistics',
            'component'  => 'report/followup-statistics',
            'permission' => 'report.followup.statistics',
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '财务报表'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '收费记录表',
            'icon'       => 'icon-inv-search',
            'path'       => '/report/cashier-list',
            'url'        => '/new#/report/finance/cashier-list',
            'meta'       => [
                'title' => '收费记录表'
            ],
            'route'      => 'ReportCashierList',
            'component'  => 'report/cashier-list',
            'permission' => 'report.cashier.list',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '收费汇总表',
            'icon'       => 'icon-paper-pay',
            'path'       => '/report/cashier-collection',
            'url'        => '/new#/report/finance/cashier-collection',
            'meta'       => [
                'title' => '收费汇总表'
            ],
            'route'      => 'ReportCashierCollection',
            'component'  => 'report/cashier-collection',
            'permission' => 'report.cashier.collect',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '预收账款表',
            'icon'       => 'icon-paper-pay',
            'path'       => '/report/cashier-deposit',
            'route'      => 'ReportCashierDeposit',
            'url'        => '/new#/report/finance/cashier-deposit',
            'meta'       => [
                'title' => '预收账款表'
            ],
            'remark'     => '资产负债表中:预收账款表',
            'permission' => 'report.cashier.deposit',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '应收账款表',
            'icon'       => 'icon-inv-search',
            'path'       => '/report/cashier-arrearage',
            'url'        => '/new#/report/finance/cashier-arrearage',
            'meta'       => [
                'title' => '应收账款表'
            ],
            'route'      => 'ReportCashierArrearage',
            'component'  => 'report/cashier-arrearage',
            'remark'     => '资产负债表中:应收账款表',
            'permission' => 'report.cashier.arrearage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '职工工作明细表',
            'icon'       => 'iconfont if-excel',
            'url'        => '/new#/report/finance/performance-sales',
            'path'       => '/report/performance-sales',
            'route'      => 'ReportPerformanceSales',
            'meta'       => [
                'title' => '职工工作明细表'
            ],
            'component'  => 'report/performance-sales',
            'remark'     => '查看职工工作业绩',
            'permission' => 'report.performance.sales',
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '进销存报表'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '进货入库明细表',
            'icon'       => 'iconfont if-purchase',
            'path'       => '/report/purchase-detail',
            'url'        => '/new#/report/erp/purchase-detail',
            'meta'       => [
                'title' => '进货入库明细表'
            ],
            'route'      => 'ReportPurchaseDetail',
            'component'  => 'report/purchase-detail',
            'permission' => 'report.purchase.detail'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '科室领料明细表',
            'icon'       => 'icon-edit',
            'path'       => '/report/department-picking-detail',
            'url'        => '/new#/report/department-picking-detail',
            'meta'       => [
                'title' => '科室领料明细表'
            ],
            'route'      => 'ReportDepartmentPickingDetail',
            'component'  => 'report/department-picking-detail',
            'permission' => 'report.department.picking.detail',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '用料登记明细表',
            'icon'       => 'icon-pill',
            'path'       => '/report/consumable-detail',
            'route'      => 'ReportConsumableDetail',
            'url'        => '/new#/report/erp/consumable-detail',
            'meta'       => [
                'title' => '用料登记明细表'
            ],
            'component'  => 'report/consumable-detail',
            'permission' => 'report.consumable.detail',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '零售出料明细表',
            'icon'       => 'icon-paper-money',
            'path'       => '/report/retail-outbound-detail',
            'url'        => '/new#/report/erp/retail-outbound-detail',
            'meta'       => [
                'title' => '零售出料明细表'
            ],
            'route'      => 'ReportRetailOutboundDetail',
            'component'  => 'report/retail-outbound-detail',
            'permission' => 'report.retail.outbound.detail'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '库存变动明细表',
            'icon'       => 'icon-find-paid-det',
            'path'       => '/report/inventory-detail',
            'url'        => '/new#/report/erp/inventory-detail',
            'meta'       => [
                'title' => '库存变动明细表'
            ],
            'route'      => 'ReportInventoryDetail',
            'component'  => 'report/inventory-detail',
            'permission' => 'report.inventory.detail',
        ]);
    }

    public function createMemberCenterMenu()
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '顾客运营',
            'icon'     => 'iconfont if-customer'
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '顾客管理',
            'url'        => '/new#/customer/index',
            'path'       => '/customer/index',
            'route'      => 'CustomerIndex',
            'meta'       => [
                'title' => '顾客管理'
            ],
            'component'  => 'customer/index',
            'permission' => 'customer.manage',
            'icon'       => 'iconfont if-customer'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '顾客建档',
            'icon'       => 'iconfont if-plus',
            'display'    => false,
            'permission' => 'customer.create'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看档案',
            'display'    => false,
            'remark'     => '查看顾客档案信息',
            'permission' => 'customer.info'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '更新档案',
            'display'    => false,
            'remark'     => '更新顾客档案信息',
            'permission' => 'customer.update'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '删除档案',
            'display'    => false,
            'icon'       => 'icon-remove',
            'remark'     => '删除顾客档案信息',
            'permission' => 'customer.remove',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '显示电话',
            'icon'       => 'icon-view',
            'remark'     => '显示顾客电话号码',
            'display'    => false,
            'permission' => 'customer.phone',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '更新电话',
            'icon'       => 'icon-telephone',
            'remark'     => '显示电话权限必须开启',
            'display'    => false,
            'permission' => 'customer.update.phone',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '网电咨询',
            'remark'     => '显示网电咨询记录',
            'display'    => false,
            'permission' => 'customer.reservation',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '咨询记录',
            'remark'     => '显示顾客现场咨询记录',
            'display'    => false,
            'permission' => 'customer.consultant',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '预约记录',
            'icon'       => 'icon-make_oppointment',
            'remark'     => '显示管理客户预约记录',
            'display'    => false,
            'permission' => 'customer.appointment',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '已购项目',
            'icon'       => 'icon-find-fee-itm',
            'remark'     => '显示顾客成交项目明细',
            'display'    => false,
            'permission' => 'customer.product',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '已购物品',
            'icon'       => 'icon-drug',
            'remark'     => '显示顾客成交物品明细(存药管理)',
            'display'    => false,
            'permission' => 'customer.goods',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '治疗记录',
            'icon'       => 'icon-injector',
            'remark'     => '显示顾客治疗记录',
            'display'    => false,
            'permission' => 'customer.treatment',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '收费记录',
            'icon'       => 'icon-paper-pay',
            'remark'     => '收费记录',
            'display'    => false,
            'permission' => 'customer.cashier',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '二开记录',
            'icon'       => 'icon-currency',
            'remark'     => '显示二开零购记录',
            'display'    => false,
            'permission' => 'customer.erkai',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '回访记录',
            'remark'     => '显示顾客所有回访记录',
            'display'    => false,
            'permission' => 'customer.followup',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '对比照片',
            'icon'       => 'icon-img',
            'remark'     => '顾客术前术后对比照',
            'display'    => false,
            'permission' => 'customer.photo',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '卡券信息',
            'icon'       => 'icon-coupon',
            'remark'     => '显示顾客持有的卡券信息',
            'display'    => false,
            'permission' => 'customer.coupon',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '操作日志',
            'remark'     => '顾客信息变更详细日志',
            'display'    => false,
            'permission' => 'customer.log',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '短信记录',
            'icon'       => 'icon-sms',
            'display'    => false,
            'permission' => 'customer.sms',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '沟通记录',
            'display'    => false,
            'permission' => 'customer.talk'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '修改亲友关系',
            'remark'     => '修改顾客亲友关系',
            'display'    => false,
            'permission' => 'customer.update.qufriend',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '合并档案',
            'icon'       => 'icon-merge',
            'remark'     => '合并顾客档案',
            'display'    => false,
            'permission' => 'customer.merge',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '调整积分',
            'icon'       => 'icon-money',
            'remark'     => '手工加减积分',
            'display'    => false,
            'permission' => 'integral.adjust',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '导出客户',
            'icon'       => 'icon-excel',
            'display'    => false,
            'remark'     => '导出客户信息',
            'permission' => 'customer.export',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '导入客户',
            'icon'       => 'icon-import',
            'display'    => false,
            'remark'     => '导入客户信息',
            'permission' => 'customer.import',
        ]);

        $batch = WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '批量操作',
            'icon'       => 'icon-replace-order',
            'display'    => false,
            'remark'     => '批量操作',
            'permission' => 'customer.batch.operation'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '更换开发',
            'icon'       => 'icon-redo',
            'display'    => false,
            'remark'     => '批量修改归属开发人员',
            'permission' => 'customer.batch.ascription'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '更换现场',
            'icon'       => 'icon-redo',
            'display'    => false,
            'remark'     => '批量修改归属现场顾问',
            'permission' => 'customer.batch.consultant'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '更换专属客服',
            'icon'       => 'icon-user',
            'display'    => false,
            'remark'     => '批量更换顾客专属客服',
            'permission' => 'customer.batch.service'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '更换主治医生',
            'icon'       => 'icon-doctor',
            'display'    => false,
            'remark'     => '批量更换顾客主治医生',
            'permission' => 'customer.batch.doctor'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '设置回访',
            'icon'       => 'iconfont if-followup',
            'display'    => false,
            'remark'     => '批量设置回访',
            'permission' => 'customer.batch.followup'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '设置标签',
            'icon'       => 'iconfont if-tag',
            'display'    => false,
            'remark'     => '批量设置标签',
            'permission' => 'customer.batch.tag'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '加入分群',
            'icon'       => 'icon-edit',
            'display'    => false,
            'remark'     => '加入分群',
            'permission' => 'customer.batch.group.join'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '更改分群',
            'icon'       => 'icon-edit',
            'display'    => false,
            'remark'     => '更改分群',
            'permission' => 'customer.batch.group.change'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '移出分群',
            'icon'       => 'icon-edit',
            'display'    => false,
            'remark'     => '移出分群',
            'permission' => 'customer.batch.group.remove'
        ]);

        WebMenu::query()->create([
            'parentid'   => $batch->id,
            'name'       => '发送短信',
            'icon'       => 'icon-sms',
            'display'    => false,
            'remark'     => '发送短信',
            'permission' => 'customer.batch.sms'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '顾客分群',
            'url'        => '/new#/customer/group',
            'path'       => '/customer/group',
            'icon'       => 'iconfont if-customer-group',
            'meta'       => [
                'title' => '顾客分群'
            ],
            'route'      => 'CustomerGroupIndex',
            'permission' => 'customer.group',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '数据维护',
            'icon'       => 'iconfont if-tool',
            'path'       => '/customer/data-maintenance',
            'meta'       => [
                'title' => '数据维护'
            ],
            'route'      => 'DataMaintenanceIndex',
            'component'  => 'data-maintenance/index',
            'remark'     => '可以任意修改客户数据,慎用!',
            'permission' => 'data.maintenance',
        ]);
    }

    public function createErpMenu()
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '库存中心',
            'icon'     => 'iconfont if-home'
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '基础档案'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '仓库管理',
            'icon'       => 'iconfont if-warehouse',
            'url'        => '/new#/erp/base/warehouse',
            'path'       => '/erp/base/warehouse',
            'meta'       => [
                'title' => '仓库管理'
            ],
            'route'      => 'WarehouseIndex',
            'permission' => 'warehouse.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '计量单位',
            'icon'       => 'iconfont if-unit',
            'url'        => '/new#/erp/base/unit',
            'path'       => '/erp/base/unit',
            'meta'       => [
                'title' => '计量单位'
            ],
            'route'      => 'UnitIndex',
            'component'  => 'unit/index',
            'permission' => 'unit.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '物品维护',
            'url'        => '/new#/erp/base/goods',
            'icon'       => 'iconfont if-goods',
            'path'       => '/erp/base/goods',
            'meta'       => [
                'title' => '物品维护'
            ],
            'route'      => 'GoodsIndex',
            'component'  => 'goods/index',
            'permission' => 'goods.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '药品维护',
            'url'        => '/new#/erp/base/drug',
            'icon'       => 'iconfont if-drug',
            'path'       => '/erp/base/drug',
            'meta'       => [
                'title' => '药品维护'
            ],
            'route'      => 'DrugIndex',
            'component'  => 'drug/index',
            'permission' => 'drug.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '供应厂商',
            'url'        => '/new#/erp/base/supplier',
            'icon'       => 'iconfont if-supplier',
            'path'       => '/erp/base/supplier',
            'meta'       => [
                'title' => '供应厂商'
            ],
            'route'      => 'SupplierIndex',
            'component'  => 'supplier/index',
            'permission' => 'supplier.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '生产厂商',
            'url'        => '/new#/erp/base/manufacturer',
            'icon'       => 'iconfont if-manufacturer',
            'path'       => '/erp/base/manufacturer',
            'meta'       => [
                'title' => '生产厂商'
            ],
            'route'      => 'ManufacturerIndex',
            'component'  => 'manufacturer/index',
            'permission' => 'manufacturer.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '入库类别',
            'url'        => '/new#/erp/base/purchase-type',
            'icon'       => 'iconfont if-tool',
            'remark'     => '入库类别管理',
            'path'       => '/erp/base/purchase-type',
            'meta'       => [
                'title' => '入库类别'
            ],
            'route'      => 'PurchaseTypeIndex',
            'component'  => 'purchase-type/index',
            'permission' => 'purchase.type.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '领料类别',
            'url'        => '/new#/erp/base/department-picking-type',
            'icon'       => 'iconfont if-tool',
            'remark'     => '科室领料出库类别',
            'path'       => '/erp/base/department-picking-type',
            'meta'       => [
                'title' => '领料类别'
            ],
            'route'      => 'DepartmentPickingType',
            'component'  => 'department-picking-type/index',
            'permission' => 'department.picking.type',
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '库存管理',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看成本',
            'icon'       => 'icon-view',
            'remark'     => '查看商品成本价',
            'display'    => false,
            'permission' => 'view.purchase.price'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '进货入库',
            'icon'       => 'iconfont if-purchase',
            'url'        => '/new#/erp/order/purchase',
            'path'       => '/inventory/purchase',
            'meta'       => [
                'title' => '进货入库'
            ],
            'route'      => 'PurchaseIndex',
            'component'  => 'purchase/index',
            'permission' => 'purchase.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '退货出库',
            'icon'       => 'icon-redo',
            'url'        => '/new#/erp/order/purchase-return',
            'path'       => '/purchase-return/purchase',
            'meta'       => [
                'title' => '退货出库'
            ],
            'route'      => 'PurchaseReturnIndex',
            'component'  => 'purchase-return/index',
            'remark'     => '有时由于销售或质量原因，我们需要将商品退还给供货商',
            'permission' => 'purchase.return.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '零售出料',
            'icon'       => 'icon-paper-money',
            'path'       => '/retail-outbound/index',
            'meta'       => [
                'title' => '零售出料'
            ],
            'route'      => 'RetailOutboundIndex',
            'component'  => 'retail-outbound/index',
            'remark'     => '用于 零售、零购 缴费后产品出库操作',
            'permission' => 'retail.outbound',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '科室领料',
            'icon'       => 'icon-edit',
            'url'        => '/new#/erp/order/department-picking',
            'path'       => '/department-picking/index',
            'meta'       => [
                'title' => '科室领料'
            ],
            'route'      => 'DepartmentPickingIndex',
            'component'  => 'department-picking/index',
            'remark'     => '直接出料到科室',
            'permission' => 'department.picking',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '库存查询',
            'url'        => '/new#/erp/order/inventory',
            'icon'       => 'icon-inv-search',
            'path'       => '/inventory/index',
            'meta'       => [
                'title' => '库存查询'
            ],
            'route'      => 'InventoryIndex',
            'component'  => 'inventory/index',
            'remark'     => '查询每个商品在每个仓库的信息',
            'permission' => 'inventory.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '批次管理',
            'url'        => '/new#/erp/order/inventory-batchs',
            'icon'       => 'icon-search',
            'path'       => '/inventory-batchs/index',
            'meta'       => [
                'title' => '批次管理'
            ],
            'route'      => 'InventoryBatchsIndex',
            'component'  => 'inventory-batchs/index',
            'remark'     => '查询每一个批次的变动信息',
            'permission' => 'inventory.batchs',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '库存调拨',
            'url'        => '/new#/erp/order/inventory-transfer',
            'icon'       => 'icon-transfer',
            'path'       => '/inventory-transfer/index',
            'meta'       => [
                'title' => '库存调拨'
            ],
            'route'      => 'InventoryTransferIndex',
            'component'  => 'inventory-transfer/index',
            'permission' => 'inventory.transfer',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '报损单',
            'url'        => '/new#/erp/order/inventory-loss',
            'icon'       => 'icon-red-cancel-paper',
            'path'       => '/inventory-loss/index',
            'meta'       => [
                'title' => '报损单'
            ],
            'route'      => 'InventoryLossIndex',
            'component'  => 'inventory-loss/index',
            'permission' => 'inventory.loss',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '报溢单',
            'url'        => '/new#/erp/order/inventory-overflow',
            'icon'       => 'icon-rebill',
            'path'       => '/inventory-overflow/index',
            'meta'       => [
                'title' => '报溢单'
            ],
            'route'      => 'InventoryOverflowIndex',
            'component'  => 'inventory-overflow/index',
            'permission' => 'inventory.overflow',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '库存盘点',
            'icon'       => 'icon-adjust-inventory',
            'path'       => '/inventory-check/index',
            'meta'       => [
                'title' => '库存盘点'
            ],
            'route'      => 'InventoryCheckIndex',
            'component'  => 'inventory-check/index',
            'permission' => 'inventory.check',
        ]);
    }

    public function createSystemSettingMenu(): void
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '系统管理',
            'icon'     => 'iconfont if-setting'
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '门店管理',
            'icon'     => 'iconfont if-store'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '员工管理',
            'icon'       => 'iconfont if-user',
            'path'       => '/system/user',
            'meta'       => [
                'title' => '员工管理'
            ],
            'route'      => 'UserIndex',
            'component'  => 'user/index',
            'permission' => 'user.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'url'        => '/new#/system/store/department',
            'path'       => '/system/department',
            'name'       => '部门管理',
            'icon'       => 'iconfont if-home',
            'meta'       => [
                'title' => '部门管理'
            ],
            'route'      => 'DepartmentIndex',
            'permission' => 'department.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '角色管理',
            'path'       => '/system/role',
            'meta'       => [
                'title' => '角色管理'
            ],
            'route'      => 'RoleIndex',
            'component'  => 'role/index',
            'permission' => 'role.manage',
            'icon'       => 'iconfont if-role'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '工作组管理',
            'icon'       => 'iconfont if-parameter',
            'path'       => '/system/user-group',
            'url'        => '/new#/system/store/user-group',
            'meta'       => [
                'title' => '工作组管理'
            ],
            'route'      => 'UserGroupIndex',
            'permission' => 'user.group.manage'
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '安全管理',
            'icon'     => 'iconfont if-safe'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => 'IP白名单',
            'icon'       => 'iconfont if-item',
            'path'       => '/system/whitelist',
            'url'        => '/new#/system/safety/whitelist',
            'route'      => 'WhiteListIndex',
            'meta'       => [
                'title' => 'IP白名单'
            ],
            'component'  => 'whitelist/index',
            'remark'     => '设置允许登录系统的IP白名单',
            'permission' => 'whitelist.index'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '令牌管理',
            'icon'       => 'iconfont if-password',
            'url'        => '/new#/system/safety/token',
            'path'       => '/system/token',
            'meta'       => [
                'title' => '令牌管理'
            ],
            'route'      => 'TokenIndex',
            'component'  => 'token/index',
            'remark'     => '管理用户登录的令牌',
            'permission' => 'token.index'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '权限查询',
            'icon'       => 'iconfont if-password',
            'url'        => '/new#/system/safety/permission',
            'path'       => '/system/permission',
            'meta'       => [
                'title' => '权限查询'
            ],
            'route'      => 'PermissionQueryIndex',
            'component'  => 'permission-query/index',
            'remark'     => '查询拥有菜单权限的员工',
            'permission' => 'permission.query.index'
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '连锁管理',
            'icon'     => 'iconfont if-organization',
            'remark'   => '连锁管理功能,适用于总部',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '门店管理',
            'icon'       => 'iconfont if-store',
            'url'        => '/new#/system/chain/store',
            'path'       => '/system/hospital',
            'meta'       => [
                'title' => '门店管理'
            ],
            'route'      => 'HospitalIndex',
            'component'  => 'hospital/index',
            'permission' => 'hospital.manage',
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '日志管理',
            'icon'     => 'iconfont if-log'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '顾客变动日志',
            'icon'       => 'iconfont if-log',
            'url'        => '/new#/system/log/customer',
            'path'       => '/system/log/customer',
            'meta'       => [
                'title' => '顾客日志'
            ],
            'route'      => 'LogCustomer',
            'component'  => 'log/customer',
            'permission' => 'system.log.customer',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '员工登录日志',
            'icon'       => 'iconfont if-log',
            'url'        => '/new#/system/log/login',
            'path'       => '/system/log/login',
            'meta'       => [
                'title' => '员工登录日志'
            ],
            'route'      => 'LogLogin',
            'component'  => 'log/login',
            'permission' => 'system.log.login',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '报表导出日志',
            'icon'       => 'iconfont if-log',
            'url'        => '/new#/system/log/export',
            'path'       => '/system/log/export',
            'meta'       => [
                'title' => '报表导出日志'
            ],
            'route'      => 'LogExport',
            'component'  => 'log/export',
            'permission' => 'system.log.export',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '号码查看日志',
            'icon'       => 'iconfont if-log',
            'url'        => '/new#/system/log/phone',
            'path'       => '/system/log/phone',
            'meta'       => [
                'title' => '号码查看日志'
            ],
            'route'      => 'LogPhone',
            'component'  => 'log/phone',
            'permission' => 'system.log.phone',
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '系统管理',
            'icon'     => 'iconfont if-setting'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '系统参数设置',
            'icon'       => 'iconfont if-parameter',
            'path'       => '/system/parameter',
            'url'        => '/new#/system/manage/parameter',
            'meta'       => [
                'title' => '系统参数设置'
            ],
            'route'      => 'ParameterIndex',
            'component'  => 'parameter/index',
            'permission' => 'parameter.index'
        ]);
    }

    public function createCashierMenu()
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '收银管理',
            'icon'     => 'iconfont if-cashier'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '收费列表',
            'icon'       => 'icon-money',
            'path'       => '/cashier/index',
            'meta'       => [
                'title' => '收费列表'
            ],
            'route'      => 'CashierIndex',
            'component'  => 'cashier/index',
            'remark'     => '显示收费列表',
            'permission' => 'cashier.list',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '营收明细',
            'icon'       => 'icon-paper-pay',
            'url'        => '/new#/cashier/detail/index',
            'path'       => '/cashier-detail/index',
            'meta'       => [
                'title' => '营收明细'
            ],
            'route'      => 'CashierDetailIndex',
            'permission' => 'cashier.detail',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看营收明细详情',
            'display'    => false,
            'permission' => 'cashier.detail.index'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '导出',
            'remark'     => '导出营收明细详情',
            'display'    => false,
            'permission' => 'cashier.detail.export'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '零售收费',
            'icon'       => 'iconfont if-cashier',
            'path'       => '/cashier-retail/index',
            'meta'       => [
                'title' => '零售收费'
            ],
            'route'      => 'CashierRetailIndex',
            'component'  => 'cashier-retail/index',
            'remark'     => '用于快速收费',
            'permission' => 'cashier.retail',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '开票管理',
            'icon'       => 'iconfont if-money-collect',
            'path'       => '/cashier-invoice/index',
            'meta'       => [
                'title' => '开票管理'
            ],
            'route'      => 'CashierInvoiceIndex',
            'component'  => 'cashier-invoice/index',
            'remark'     => '记录开票信息',
            'permission' => 'cashier.invoice',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '退款申请',
            'icon'       => 'icon-money-delete',
            'path'       => '/cashier-refund/index',
            'meta'       => [
                'title' => '退款申请'
            ],
            'route'      => 'CashierRefundIndex',
            'component'  => 'cashier-refund/index',
            'permission' => 'cashier.refund',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '账户流水',
            'icon'       => 'icon-inv-search',
            'path'       => '/cashier-pay/index',
            'url'        => '/new#/cashier/pay/index',
            'meta'       => [
                'title' => '账户流水'
            ],
            'route'      => 'CashierPayIndex',
            'component'  => 'cashier-pay/index',
            'permission' => 'cashier.pay',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看账户流水详情',
            'display'    => false,
            'permission' => 'cashier.pay.index'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '更新',
            'remark'     => '更新支付方式信息',
            'display'    => false,
            'permission' => 'cashier.pay.update'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '导出',
            'remark'     => '导出流水明细表',
            'display'    => false,
            'permission' => 'cashier.pay.export'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '欠款管理',
            'icon'       => 'iconfont if-arrearage',
            'path'       => '/cashier-arrearage/index',
            'meta'       => [
                'title' => '欠款管理'
            ],
            'route'      => 'CashierArrearageIndex',
            'component'  => 'cashier-arrearage/index',
            'permission' => 'cashier.arrearage',
        ]);

    }

    public function createMarketingMenu()
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '营销中心',
            'icon'     => 'iconfont if-target',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '渠道管理',
            'icon'       => 'iconfont if-organization',
            'path'       => '/market/channel',
            'url'        => '/new#/market/channel',
            'meta'       => [
                'title' => '渠道管理'
            ],
            'route'      => 'MarketChannelIndex',
            'permission' => 'market.channel',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看渠道',
            'display'    => false,
            'permission' => 'market.channel.index'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '编辑',
            'remark'     => '编辑渠道',
            'display'    => false,
            'permission' => 'market.channel.update'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '删除',
            'remark'     => '删除渠道',
            'display'    => false,
            'permission' => 'market.channel.index'
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '小程序',
            'icon'     => 'iconfont if-mini-program',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '用户管理',
            'icon'       => 'iconfont if-customer',
            'path'       => '/market/miniapp-user/index',
            'meta'       => [
                'title' => '用户管理'
            ],
            'route'      => 'MiniappUserIndex',
            'component'  => 'miniapp-user/index',
            'permission' => 'miniapp.user.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '营销素材',
            'icon'       => 'iconfont if-photo',
            'path'       => '/market/marketing-material/index',
            'meta'       => [
                'title' => '营销素材'
            ],
            'route'      => 'MarketingMaterialIndex',
            'component'  => 'marketing-material/index',
            'permission' => 'materials.index',
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '短信管理',
            'icon'     => 'iconfont if-sms'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '短信概况',
            'icon'       => 'iconfont if-dashboard',
            'path'       => '/market/sms/dashboard',
            'url'        => '/new#/market/sms/dashboard',
            'meta'       => [
                'title' => '短信概况'
            ],
            'route'      => 'SmsDashboard',
            'component'  => 'sms/dashboard',
            'permission' => 'sms.dashboard'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '短信记录',
            'icon'       => 'iconfont if-log',
            'path'       => '/market/sms/index',
            'meta'       => [
                'title' => '短信记录'
            ],
            'route'      => 'SmsIndex',
            'component'  => 'sms/index',
            'permission' => 'sms.manage'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '短信模板',
            'icon'       => 'iconfont if-template',
            'path'       => '/market/sms-template/index',
            'url'        => '/new#/market/sms/template',
            'meta'       => [
                'title' => '短信模板'
            ],
            'route'      => 'SmsTemplateIndex',
            'component'  => 'sms-template/index',
            'permission' => 'sms.template.manage',
        ]);

        $menu = WebMenu::query()->create([
            'parentid' => $root->id,
            'name'     => '卡券管理',
            'icon'     => 'iconfont if-tag'
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '卡券管理',
            'icon'       => 'icon-coupon',
            'path'       => '/market/coupon/index',
            'meta'       => [
                'title' => '卡券管理'
            ],
            'route'      => 'CouponIndex',
            'component'  => 'coupon/index',
            'remark'     => '发放代金券,吸引顾客消费',
            'permission' => 'coupon.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '领券记录',
            'icon'       => 'icon-star',
            'remark'     => '卡券发放明细',
            'path'       => '/market/coupon-detail/index',
            'meta'       => [
                'title' => '领券记录'
            ],
            'route'      => 'CouponDetailIndex',
            'component'  => 'coupon-detail/index',
            'permission' => 'coupon.detail',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '使用记录',
            'icon'       => 'icon-excel',
            'remark'     => '卡券使用记录',
            'path'       => '/market/coupon-cashier/index',
            'meta'       => [
                'title' => '使用记录'
            ],
            'route'      => 'CouponCashierIndex',
            'component'  => 'coupon-cashier/index',
            'permission' => 'cashier.coupon',
        ]);

        // $menu = WebMenu::query()->create([
        //     'parentid' => $root->id,
        //     'name'     => '小程序',
        //     'icon'     => 'iconfont if-mini-program'
        // ]);

        // WebMenu::query()->create([
        //     'parentid'   => $menu->id,
        //     'name'       => '门店设置',
        //     'icon'       => 'iconfont if-store',
        //     'route'      => 'MiniProgramSetting',
        //     'remark'     => '小程序门店设置',
        //     'permission' => 'mini.program.setting'
        // ]);
    }

    public function createTreatmentMenu()
    {
        $root = WebMenu::query()->create([
            'parentid' => 0,
            'name'     => '科室消费',
            'icon'     => 'iconfont if-treatment',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '治疗划扣',
            'icon'       => 'iconfont if-injector',
            'path'       => '/treatment/index',
            'meta'       => [
                'title' => '治疗划扣'
            ],
            'route'      => 'TreatmentIndex',
            'component'  => 'treatment/index',
            'permission' => 'treatment.manage',
        ]);

        $menu = WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '治疗记录',
            'icon'       => 'iconfont if-record',
            'path'       => '/treatment/record',
            'url'        => '/new#/treatment/record',
            'meta'       => [
                'title' => '治疗记录'
            ],
            'route'      => 'TreatmentRecord',
            'permission' => 'treatment.record',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '查看',
            'remark'     => '查看治疗记录',
            'display'    => false,
            'permission' => 'treatment.record.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '撤销',
            'remark'     => '撤销治疗记录',
            'display'    => false,
            'permission' => 'treatment.record.undo',
        ]);

        WebMenu::query()->create([
            'parentid'   => $menu->id,
            'name'       => '导出',
            'remark'     => '导出治疗记录',
            'display'    => false,
            'permission' => 'treatment.record.export',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '用料登记',
            'icon'       => 'icon-pill',
            'remark'     => '客户用料登记',
            'path'       => '/treatment/consumable',
            'meta'       => [
                'title' => '用料登记'
            ],
            'route'      => 'ConsumableIndex',
            'component'  => 'consumable/index',
            'permission' => 'consumable.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '照片档案',
            'icon'       => 'iconfont if-photo',
            'remark'     => '管理顾客术前术后对比照',
            'path'       => '/treatment/photo',
            'meta'       => [
                'title' => '照片档案'
            ],
            'route'      => 'CustomerPhotoIndex',
            'component'  => 'customer-photo/index',
            'permission' => 'customer.photo.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '二开零购',
            'icon'       => 'iconfont if-shopping-cart',
            'path'       => '/treatment/erkai',
            'meta'       => [
                'title' => '二开零购'
            ],
            'route'      => 'ErKaiIndex',
            'component'  => 'erkai/index',
            'remark'     => '主要用于:科室二开零购、销售化妆品等',
            'permission' => 'erkai.manage',
        ]);
    }

    public function createAppointmentMenu(): void
    {
        $root = WebMenu::query()->create([
            'parentid'   => 0,
            'name'       => '预约中心',
            'icon'       => 'iconfont if-appointment',
            'permission' => 'appointment.manage',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '预约看板',
            'icon'       => 'iconfont if-dashboard',
            'url'        => '/new#/appointment/dashboard',
            'path'       => '/appointment/dashboard',
            'meta'       => [
                'title' => '预约看板'
            ],
            'route'      => 'YuyueDashboard',
            'component'  => 'yuyue/dashboard',
            'permission' => 'appointment.dashboard',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '排班管理',
            'icon'       => 'iconfont if-schedule-user',
            'url'        => '/new#/schedule/index',
            'path'       => '/schedule/index',
            'meta'       => [
                'title' => '排班管理'
            ],
            'route'      => 'ScheduleIndex',
            'component'  => 'schedule/index',
            'permission' => 'schedule.index',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '班次设置',
            'icon'       => 'iconfont if-schedule-setting',
            'url'        => '/new#/schedule/rule',
            'path'       => '/schedule/rule',
            'meta'       => [
                'title' => '班次设置'
            ],
            'route'      => 'ScheduleRule',
            'component'  => 'schedule/rule',
            'permission' => 'schedule.rule',
        ]);
    }

    private function createAppMenu(): void
    {
        $root = WebMenu::query()->create([
            'parentid'   => 0,
            'name'       => '顾客管理',
            'icon'       => 'iconfont if-customer',
            'type'       => 'app',
            'permission' => 'app.customer'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '顾客建档',
            'icon'       => 'iconfont if-plus',
            'type'       => 'app',
            'remark'     => '创建顾客档案',
            'permission' => 'app.customer.create'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '更新档案',
            'type'       => 'app',
            'remark'     => '更新顾客基础信息',
            'permission' => 'app.customer.update'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '历史回访',
            'icon'       => 'iconfont if-followup',
            'type'       => 'app',
            'remark'     => '查看顾客历史回访',
            'permission' => 'app.customer.followup'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '照片档案',
            'icon'       => 'iconfont if-photo',
            'type'       => 'app',
            'remark'     => '查看顾客照片档案',
            'permission' => 'app.customer.photo',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '预约记录',
            'icon'       => 'iconfont if-appointment',
            'type'       => 'app',
            'remark'     => '查看顾客预约记录',
            'permission' => 'app.customer.appointment',
        ]);

        $root = WebMenu::query()->create([
            'parentid'   => 0,
            'name'       => '回访管理',
            'icon'       => 'iconfont if-followup',
            'type'       => 'app',
            'permission' => 'app.followup',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '添加回访',
            'type'       => 'app',
            'remark'     => '设置回访计划或新增回访',
            'permission' => 'app.followup.create',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '更新回访',
            'type'       => 'app',
            'remark'     => '修改回访信息',
            'permission' => 'app.followup.update',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '执行回访',
            'type'       => 'app',
            'remark'     => '执行回访任务',
            'permission' => 'app.followup.execute',
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '删除回访',
            'type'       => 'app',
            'permission' => 'app.followup.remove',
        ]);

        $root = WebMenu::query()->create([
            'parentid'   => 0,
            'name'       => '照片档案',
            'icon'       => 'iconfont if-photo',
            'type'       => 'app',
            'permission' => 'app.photo'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '创建相册',
            'type'       => 'app',
            'remark'     => '创建顾客照片档案',
            'permission' => 'app.photo.create'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '更新相册',
            'type'       => 'app',
            'remark'     => '更新顾客照片档案',
            'permission' => 'app.photo.update'
        ]);

        $root = WebMenu::query()->create([
            'parentid'   => 0,
            'name'       => '预约管理',
            'icon'       => 'iconfont if-appointment',
            'type'       => 'app',
            'remark'     => '管理预约信息',
            'permission' => 'app.appointment'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '创建预约',
            'type'       => 'app',
            'remark'     => '创建预约记录',
            'permission' => 'app.appointment.create'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '更新预约',
            'type'       => 'app',
            'remark'     => '更新预约记录',
            'permission' => 'app.appointment.update'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '删除预约',
            'type'       => 'app',
            'remark'     => '删除预约记录',
            'permission' => 'app.appointment.remove'
        ]);

        $root = WebMenu::query()->create([
            'parentid'   => 0,
            'name'       => '二开零购',
            'icon'       => 'iconfont if-shopping-cart',
            'type'       => 'app',
            'remark'     => '一般提供给美容师二开使用',
            'permission' => 'app.erkai'
        ]);

        WebMenu::query()->create([
            'parentid'   => $root->id,
            'name'       => '新增',
            'type'       => 'app',
            'remark'     => '允许开单',
            'permission' => 'app.erkai.create'
        ]);
    }
}
