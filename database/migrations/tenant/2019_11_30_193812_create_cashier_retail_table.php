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
        Schema::create('cashier_retail', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->uuid('cashier_id')->nullable()->comment('收费单号');
            $table->integer('medium_id')->comment('媒介来源');
            $table->tinyInteger('type')->comment('接诊类型(初诊、复诊..)');
            $table->tinyInteger('status')->comment('业务状态(1:挂账、2:成交)');
            $table->decimal('payable', 14, 4)->default(0)->comment('应收金额');
            $table->decimal('income', 14, 4)->default(0)->comment('实收金额(不包括余额支付)');
            $table->decimal('deposit', 14, 4)->default(0)->comment('余额支付');
            $table->decimal('coupon', 14, 4)->default(0)->comment('券支付');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->text('detail')->nullable()->comment('明细');
            $table->integer('user_id')->comment('录单人员');
            $table->timestamps();
            $table->comment('零售收费表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_retail');
    }
};
