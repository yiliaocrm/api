<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('cashier_detail', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('cashier_id')->index()->comment('关联收费中心id');
            $table->uuid('customer_id')->index()->comment('顾客id');

            // 关联
            $table->string('cashierable_type')->comment('订单类型');
            $table->string('table_name', 50)->comment('关联业务表名(reception_order)');
            $table->uuid('table_id')->comment('关联业务主键id');

            // 业务信息
            $table->integer('package_id')->nullable()->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->integer('product_id')->nullable()->comment('产品id');
            $table->string('product_name')->nullable()->comment('产品名称');
            $table->integer('goods_id')->nullable()->comment('物品id');
            $table->string('goods_name')->nullable()->comment('物品名称');
            $table->integer('times')->comment('使用次数(数量)');
            $table->integer('unit_id')->nullable()->comment('单位(仅限物品)');
            $table->string('specs')->nullable()->comment('规格');

            $table->decimal('payable', 14, 4)->comment('本单应收金额');
            $table->decimal('income', 14, 4)->comment('收款(实收)金额');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->decimal('deposit', 14, 4)->comment('余额支付');
            $table->decimal('coupon', 14, 4)->default(0)->comment('券支付');
            $table->integer('department_id')->comment('结算科室(业绩归属)');

            $table->text('salesman')->nullable()->comment('销售人员(允许多个)json格式');
            $table->integer('user_id')->comment('收银员(操作员)');
            $table->timestamps();
            $table->index(['cashier_id', 'customer_id', 'created_at']);
            $table->comment('营收明细');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_detail');
    }
};
