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
        Schema::create('customer_product', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('cashier_id')->index()->comment('关联收费中心id');
            $table->uuid('cashier_detail_id')->comment('营收明细表id');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->integer('product_id')->comment('项目id');
            $table->string('product_name')->comment('产品名称');
            $table->integer('package_id')->nullable()->default(null)->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->tinyInteger('status')->comment('项目状态(1:等待划扣、2:完成治疗、3:项目退费、4:项目过期、5:疗程中)');
            $table->date('expire_time')->nullable()->default(null)->comment('项目过期日期');
            $table->integer('times')->comment('项目次数');
            $table->integer('used')->comment('已用次数');
            $table->integer('leftover')->comment('剩余次数');
            $table->integer('refund_times')->comment('退款次数');
            $table->decimal('invoice_amount', 14, 4)->default(0)->comment('开票金额');
            $table->decimal('price', 14, 4)->comment('项目原价');
            $table->decimal('sales_price', 14, 4)->comment('执行价格');
            $table->decimal('payable', 14, 4)->default(0)->comment('应收金额(成交价)');
            $table->decimal('income', 14, 4)->default(0)->comment('实收金额(不包括余额支付)');
            $table->decimal('deposit', 14, 4)->default(0)->comment('余额支付');
            $table->decimal('coupon', 14, 4)->default(0)->comment('卷额支付');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->integer('user_id')->comment('收费人员');
            $table->integer('consultant')->nullable()->comment('现场咨询');
            $table->integer('ek_user')->nullable()->comment('二开人员');
            $table->integer('doctor')->nullable()->comment('助诊医生');
            $table->tinyInteger('reception_type')->index()->comment('接诊类型');
            $table->integer('medium_id')->comment('媒介来源');
            $table->integer('department_id')->comment('结算科室');
            $table->integer('deduct_department')->comment('划扣科室');
            $table->text('salesman')->nullable()->comment('销售人员(允许多个)json格式');
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->comment('顾客项目表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_product');
    }
};
