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
        Schema::create('customer_goods', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('cashier_id')->index()->comment('关联收费中心id');
            $table->uuid('cashier_detail_id')->comment('营收明细表id');
            $table->uuid('customer_id')->comment('顾客id');
            $table->integer('goods_id')->comment('项目id');
            $table->string('goods_name')->comment('产品名称');
            $table->string('specs')->nullable()->comment('规格型号');
            $table->integer('package_id')->nullable()->default(null)->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->string('cashierable_type')->comment('业务类型');
            $table->string('table_name', 50)->comment('关联业务表名');
            $table->uuid('table_id')->comment('关联业务主键id');
            $table->tinyInteger('status')->comment('物品状态(1:待出库、2:全部出库、3:过期、4:部分出库、5:退费)');
            $table->dateTime('expire_time')->nullable()->default(null)->comment('物品过期时间');
            $table->integer('number')->comment('物品数量');
            $table->integer('unit_id')->comment('基本单位');
            $table->string('unit_name', 10)->comment('单位名称');
            $table->integer('used')->comment('已用数量');
            $table->integer('leftover')->comment('剩余数量');
            $table->integer('refund_times')->comment('退款数量');
            $table->decimal('invoice_amount', 14, 4)->default(0)->comment('开票金额');
            $table->decimal('price', 14, 4)->comment('原价(项目表价格)');
            $table->decimal('payable', 14, 4)->default(0)->comment('应收金额(成交价)');
            $table->decimal('income', 14, 4)->default(0)->comment('实收金额(不包括余额支付)');
            $table->decimal('deposit', 14, 4)->default(0)->comment('余额支付');
            $table->decimal('coupon', 14, 4)->default(0)->comment('卷额支付');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->integer('user_id')->comment('录入人员');
            $table->integer('consultant')->nullable()->comment('现场咨询');
            $table->integer('ek_user')->nullable()->comment('二开人员');
            $table->integer('doctor')->nullable()->comment('助诊医生');
            $table->tinyInteger('reception_type')->comment('接诊类型');
            $table->integer('medium_id')->comment('媒介来源');
            $table->integer('department_id')->comment('结算科室');
            $table->text('salesman')->nullable()->comment('销售人员(允许多个)json格式');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_goods');
    }
};
