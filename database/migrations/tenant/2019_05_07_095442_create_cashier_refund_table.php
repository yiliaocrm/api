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
        Schema::create('cashier_refund', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->uuid('cashier_id')->nullable()->comment('收费单号');
            $table->decimal('amount', 14, 4)->comment('退款总额');
            $table->text('remark')->nullable()->comment('备注');
            $table->text('detail')->comment('明细');
            $table->tinyInteger('status')->comment('状态(1:待审核、2:待收费、3:已收费、4:退单)');
            $table->integer('user_id')->comment('录单人员');
            $table->timestamps();
            $table->comment('退款申请主表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_refund');
    }
};
