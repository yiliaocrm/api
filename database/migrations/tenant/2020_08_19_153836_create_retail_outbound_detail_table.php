<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('retail_outbound_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('retail_outbound_id')->comment('主单ID');
            $table->string('key')->comment('出料单号');
            $table->date('date')->comment('出料日期');
            $table->integer('warehouse_id')->comment('出料仓库');
            $table->integer('department_id')->comment('出料科室');
            $table->uuid('customer_id')->comment('顾客id');
            $table->uuid('customer_goods_id')->comment('顾客物品表id');
            $table->uuid('cashier_id')->index()->comment('关联收费中心id');
            $table->integer('goods_id')->comment('项目id');
            $table->string('goods_name')->comment('产品名称');
            $table->string('specs')->nullable()->comment('规格型号');
            $table->integer('package_id')->nullable()->default(null)->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->integer('inventory_batchs_id')->comment('使用批次ID');
            $table->string('batch_code')->comment('批号');
            $table->integer('manufacturer_id')->nullable()->comment('生产厂家ID');
            $table->string('manufacturer_name')->nullable()->comment('生产厂家名称');
            $table->date('production_date')->nullable()->comment('生产日期');
            $table->date('expiry_date')->nullable()->comment('过期时间');
            $table->string('sncode')->nullable()->comment('SN码(串号、唯一序列号)');
            $table->integer('number')->comment('物品数量');
            $table->integer('unit_id')->comment('退货单位');
            $table->string('unit_name', 10)->comment('退货单位名称');
            $table->decimal('price', 14, 4)->comment('单价');
            $table->decimal('amount', 14, 4)->comment('总价');
            $table->text('remark')->nullable()->comment('备注');
            $table->integer('user_id')->comment('出料人员');
            $table->integer('create_user_id')->comment('录入人员');
            $table->timestamps();
            $table->comment('零售出料明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('retail_outbound_detail');
    }
};
