<?php

namespace App\Upgrades\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class Version105 extends BaseVersion
{
    /**
     * 版本号
     */
    public function version(): string
    {
        return '1.0.5';
    }

    /**
     * 租户数据库变更
     */
    public function tenantUp(): void
    {
        $this->tenantInfo('开始执行 1.0.5 版本升级');

        $this->tenantInfo('修改表 inventory_checks');
        Schema::table('inventory_checks', function (Blueprint $table) {
            $table->integer('department_id')->comment('盘点科室')->after('warehouse_id');
            $table->integer('inventory_loss_id')->nullable()->comment('报损单ID')->after('check_time');
            $table->integer('inventory_overflow_id')->nullable()->comment('报溢单ID')->after('inventory_loss_id');
            $table->dropColumn('amount');
            $table->integer('user_id')->comment('经办人员')->change();
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：正常')->change();
        });

        $this->tenantInfo('修改表 inventory_check_details');
        Schema::table('inventory_check_details', function (Blueprint $table) {
            $table->integer('inventory_check_id')->comment('盘点主单ID')->after('id');
            $table->integer('manufacturer_id')->nullable()->comment('生产厂家ID')->after('specs');
            $table->string('manufacturer_name')->nullable()->comment('生产厂家')->after('manufacturer_id');
            $table->integer('inventory_batchs_id')->nullable()->comment('库存批次ID')->after('manufacturer_name');
            $table->string('batch_code')->comment('批号')->after('inventory_batchs_id');
            $table->date('production_date')->nullable()->comment('生产日期')->after('batch_code');
            $table->date('expiry_date')->nullable()->comment('失效日期')->after('production_date');
            $table->string('sncode')->nullable()->comment('SN码')->after('expiry_date');
            $table->integer('unit_id')->nullable()->comment('单位ID')->after('sncode');
            $table->string('unit_name')->nullable()->comment('单位名称')->after('unit_id');
            $table->decimal('book_number', 14, 4)->default(0)->comment('账面数量')->after('unit_name');
            $table->decimal('actual_number', 14, 4)->default(0)->comment('实盘数量')->after('book_number');
            $table->decimal('diff_number', 14, 4)->default(0)->comment('差异数量')->after('actual_number');
            $table->decimal('price', 14, 4)->default(0)->comment('价格')->after('diff_number');
            $table->decimal('diff_amount', 14, 4)->default(0)->comment('差异金额')->after('price');
            $table->text('remark')->nullable()->comment('备注')->after('diff_amount');
            $table->dropColumn('inventory_checks_id');
            $table->integer('warehouse_id')->comment('盘点仓库')->change();
            $table->integer('goods_id')->comment('商品ID')->change();
            $table->string('specs')->nullable()->comment('规格型号')->change();
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：正常')->change();
        });

        $this->tenantInfo('1.0.5 版本升级完成');
    }

    /**
     * 全局操作
     */
    public function globalUp(): void
    {
        $this->info('更新所有租户旧版菜单');
        Artisan::call('app:update-web-menu-command', [], $this->command->getOutput());
        $this->info('更新所有租户场景化搜索字段配置');
        Artisan::call('app:update-scene-field-command', [], $this->command->getOutput());
    }
}
