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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status')->unsigned()->comment('1:上架、2:下架、3:过期');
            $table->string('name', 100)->comment('名称');
            $table->decimal('coupon_value', 14, 4)->unsigned()->comment('面值(优惠金额)');
            $table->decimal('least_consume', 14, 4)->nullable()->comment('使用门槛:0无门槛');
            $table->integer('total')->unsigned()->comment('总发放量');
            $table->integer('issue_count')->default(0)->unsigned()->comment('已领取(已发放数量)');
            $table->integer('quota')->unsigned()->nullable()->comment('每人限领x张券,0代表不限制');
            $table->dateTime('start')->comment('活动开始时间');
            $table->dateTime('end')->comment('活动结束时间');
            $table->tinyInteger('multiple_use')->unsigned()->comment('是否允许多次使用');
            $table->decimal('sales_price', 14, 4)->comment('卡券零售价');
            $table->decimal('integrals', 14, 4)->comment('兑换积分');
            $table->string('description')->nullable()->comment('分享文案(描述)');
            $table->decimal('rate', 14, 4)->unsigned()->comment('充赠比(实收金额/面值)');
            $table->text('remark')->nullable()->comment('使用说明');
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
        Schema::dropIfExists('coupons');
    }
};
