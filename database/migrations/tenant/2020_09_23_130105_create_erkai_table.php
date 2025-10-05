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
        Schema::create('erkai', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->integer('department_id')->comment('二开科室');
            $table->tinyInteger('type')->comment('接诊类型(初诊、复诊..)');
            $table->tinyInteger('status')->comment('状态（0:未保存、1:未成交、2:成交、3:退单）');
            $table->integer('medium_id')->comment('媒介来源');
            $table->decimal('payable', 14, 4)->default(0)->comment('应收金额');
            $table->decimal('income', 14, 4)->default(0)->comment('实收金额(不包括余额支付)');
            $table->decimal('deposit', 14, 4)->default(0)->comment('余额支付');
            $table->decimal('coupon', 14, 4)->default(0)->comment('券支付');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->integer('user_id')->comment('录单人员');
            $table->timestamps();
            $table->comment('二开零购主表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('erkai');
    }
};
