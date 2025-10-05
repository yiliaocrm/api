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
        Schema::create('sales_performance', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('cashier_id')->index()->comment('关联收费中心id');
            $table->string('table_name')->comment('业务类型表(例:App\Models\Consultant)');
            $table->uuid('table_id')->comment('关联业务主键id');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->tinyInteger('position')->comment('提成岗位（1、现场咨询(销售人员)、2、开发人员、3、划扣）');
            $table->integer('user_id')->comment('提成员工');
            $table->tinyInteger('reception_type')->comment('接诊类型');

            // 业务信息
            $table->integer('package_id')->nullable()->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->integer('product_id')->nullable()->comment('产品id');
            $table->string('product_name')->nullable()->comment('产品名称');
            $table->integer('goods_id')->nullable()->comment('物品id');
            $table->string('goods_name')->nullable()->comment('物品名称');

            $table->decimal('payable', 14, 4)->comment('本单应收金额');
            $table->decimal('income', 14, 4)->comment('收款(实收)金额');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->decimal('deposit', 14, 4)->comment('余额支付');
            $table->decimal('amount', 14, 4)->comment('提成金额');
            $table->decimal('coupon', 14, 4)->default(0)->comment('券支付');
            $table->smallInteger('rate')->unsigned()->comment('提成比例 百分比');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('created_at');
            $table->comment('业绩表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_performance');
    }
};
