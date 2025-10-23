<?php

namespace Database\Seeders\Tenant;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Role::query()->truncate();
        Role::query()->create([
            'name'        => '系统管理员',
            'slug'        => 'administrators',
            'permissions' => [
                'superuser' => true
            ]
        ]);
        Role::query()->create([
            'name'        => '网电咨询',
            'slug'        => 'network',
            'permissions' => [
                "customer.manage"       => true,
                "customer.create"       => true,
                "customer.info"         => true,
                "customer.update"       => true,
                "customer.phone"        => true,
                "customer.update.phone" => true,
                "customer.reservation"  => true,
                "customer.consultant"   => true,
                "customer.product"      => true,
                "customer.goods"        => true,
                "customer.treatment"    => true,
                "customer.followup"     => true,
                "reservation.manage"    => true,
                "reservation.reception" => true,
                "reservation.create"    => true,
                "reservation.update"    => true,
                "followup.manage"       => true,
                "appointment.dashboard" => true,
                "appointment.manage"    => true,
                "schedule.manage"       => true,
                "app.member"            => true,
                "app.reservation"       => true,
                "app.appointment"       => true,
                "app.schedule"          => true

            ]
        ]);
        Role::query()->create([
            'name'        => '前台',
            'slug'        => 'reception',
            'permissions' => [
                "customer.manage"               => true,
                "customer.create"               => true,
                "customer.info"                 => true,
                "customer.update"               => true,
                "customer.view.all"             => true,
                "customer.reservation"          => true,
                "customer.consultant"           => true,
                "customer.product"              => true,
                "customer.goods"                => true,
                "customer.followup"             => true,
                "workbench.reception"           => true,
                "reception.remove"              => true,
                "reception.dispatch.consultant" => true,
                "reception.dispatch.doctor"     => true,
                "appointment.dashboard"         => true,
                "appointment.manage"            => true,
                "schedule.manage"               => true,
                "app.member"                    => true,
                "app.reception"                 => true,
                "app.appointment"               => true,
                "app.schedule"                  => true
            ]
        ]);
        Role::query()->create([
            'name'        => '现场咨询',
            'slug'        => 'consultant',
            'permissions' => [
                "customer.manage"          => true,
                "customer.create"          => true,
                "customer.info"            => true,
                "customer.update"          => true,
                "customer.phone"           => true,
                "customer.update.phone"    => true,
                "customer.reservation"     => true,
                "customer.consultant"      => true,
                "customer.product"         => true,
                "customer.goods"           => true,
                "customer.treatment"       => true,
                "customer.followup"        => true,
                "report.performance.sales" => true,
                "app.reminder"             => true,
                "consultant.manage"        => true,
                "consultant.quotation"     => true,
                "followup.manage"          => true,
                "followup.update"          => true,
                "schedule.manage"          => true,
                "app.member"               => true,
                "app.report"               => true,
                "app.consultant"           => true,
                "app.schedule"             => true
            ]
        ]);
        Role::query()->create([
            'name'        => '收银',
            'slug'        => 'cashier',
            'permissions' => [
                "customer.manage"          => true,
                "customer.create"          => true,
                "customer.info"            => true,
                "customer.update"          => true,
                "customer.view.all"        => true,
                "customer.reservation"     => true,
                "customer.consultant"      => true,
                "customer.product"         => true,
                "customer.goods"           => true,
                "report.performance.sales" => true,
                "report.cashier.list"      => true,
                "report.cashier.collect"   => true,
                "cashier.dashboard"        => true,
                "cashier.list"             => true,
                "cashier.refund"           => true,
                "cashier.retail"           => true,
                "cashier.detail"           => true,
                "cashier.pay"              => true,
                "cashier.arrearage"        => true,
                "app.member"               => true,
                "app.report"               => true,
                "app.cashier"              => true
            ]
        ]);
        Role::query()->create([
            'name'        => '医生',
            'slug'        => 'doctor',
            'execution'   => true,
            'permissions' => []
        ]);
        Role::query()->create([
            'name'        => '医生助理',
            'slug'        => 'assistant',
            'execution'   => true,
            'permissions' => []
        ]);
        Role::query()->create([
            'name'        => '技师',
            'slug'        => 'technician',
            'execution'   => true,
            'permissions' => []
        ]);
        Role::query()->create([
            'name'        => '护士',
            'slug'        => 'nurse',
            'execution'   => true,
            'permissions' => []
        ]);
        Role::query()->create([
            'name'        => '麻醉师',
            'slug'        => 'anesthetist',
            'execution'   => true,
            'permissions' => []
        ]);
    }
}
