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
        Schema::create('coupon_detail_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('coupon_id')->unsigned()->comment('卡券ID');
            $table->integer('coupon_detail_id')->unsigned()->comment('卡券明细ID');
            $table->string('coupon_number')->comment('卡券编号');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->decimal('before', 14, 4)->default(0)->comment('原有金额');
            $table->decimal('amount', 14, 4)->default(0)->comment('变动金额');
            $table->decimal('after', 14, 4)->default(0)->comment('现有金额');
            $table->string('remark')->nullable()->comment('备注');
            $table->uuidMorphs('historyable');
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
        Schema::dropIfExists('coupon_detail_histories');
    }
};
