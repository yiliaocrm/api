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
        Schema::create('cashier', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->string('key')->comment('单据编号');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->integer('status')->comment('业务状态(1:未收费,2:已收费,3:退单)');

            // 业务类型
            $table->uuid('cashierable_id');
            $table->string('cashierable_type');
            $table->index(['cashierable_id', 'cashierable_type']);

            $table->decimal('payable', 14, 4)->default(0)->comment('应收金额');
            $table->decimal('income', 14, 4)->default(0)->comment('实收金额(不包括余额支付)');
            $table->decimal('deposit', 14, 4)->default(0)->comment('余额支付');
            $table->decimal('coupon', 14, 4)->default(0)->comment('券支付');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->integer('user_id')->comment('录单人员');
            $table->integer('operator')->default(0)->comment('结单人员');
            $table->text('detail')->nullable()->comment('业务(项目)明细');
            $table->timestamps();
            $table->comment('收费中心');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier');
    }
};
