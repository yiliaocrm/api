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
        Schema::create('cashier_coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cashier_id')->index()->comment('收费单id');
            $table->integer('coupon_id')->unsigned()->comment('卡券ID');
            $table->integer('coupon_detail_id')->unsigned()->comment('卡券明细ID');
            $table->string('coupon_name')->comment('卡券名称');
            $table->string('coupon_number')->comment('卡券编号');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->decimal('income', 14, 4)->comment('付款金额');
            $table->string('remark')->nullable()->comment('备注');
            $table->integer('user_id')->comment('收银员');
            $table->timestamps();
            $table->comment('收费单卡券表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_coupons');
    }
};
