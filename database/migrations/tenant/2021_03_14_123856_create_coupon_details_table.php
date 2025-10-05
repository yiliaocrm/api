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
        Schema::create('coupon_details', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status')->unsigned()->comment('状态:1:未使用,2:部分使用,3:已使用');
            $table->integer('coupon_id')->unsigned();
            $table->string('coupon_name')->comment('卡券名称');
            $table->decimal('coupon_value')->unsigned()->comment('面值(优惠金额)');
            $table->decimal('balance')->unsigned()->comment('剩余券额');
            $table->uuid('customer_id')->index()->nullable()->comment('顾客ID');
            $table->string('number')->unique()->comment('卡券编号');
            $table->decimal('sales_price', 14, 4)->comment('卡券零售价(付款金额)');
            $table->decimal('integrals', 14, 4)->comment('兑换积分');
            $table->dateTime('expire_time')->comment('过期时间');
            $table->decimal('rate', 14, 4)->unsigned()->comment('充赠比(实收金额/面值)');
            $table->integer('department_id')->comment('结算科室(业绩归属)');
            $table->text('salesman')->nullable()->comment('销售人员(允许多个)json格式');
            $table->text('remark')->nullable();
            $table->integer('create_user_id')->unsigned()->comment('创建人员');
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
        Schema::dropIfExists('coupon_details');
    }
};
