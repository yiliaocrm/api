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
        Schema::create('customer_deposit_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->uuid('cashier_id')->index()->comment('收费单id');
            $table->uuid('cashier_detail_id')->comment('收费明细表id');
            $table->decimal('before', 14, 4)->default(0)->comment('期初余额(原有余额)');
            $table->decimal('balance', 14, 4)->default(0)->comment('本期发生(业务金额)');
            $table->decimal('after', 14, 4)->default(0)->comment('期末余额(现有余额)');

            // 关联
            $table->string('cashierable_type')->comment('订单类型');
            $table->string('table_name', 50)->comment('关联业务表名(reception_order)');
            $table->uuid('table_id')->comment('关联业务主键id');

            // 业务信息
            $table->integer('package_id')->nullable()->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->integer('product_id')->nullable()->comment('产品id');
            $table->string('product_name')->nullable()->comment('产品名称');
            $table->integer('goods_id')->nullable()->comment('物品id');
            $table->string('goods_name')->nullable()->comment('物品名称');
            $table->integer('times')->comment('使用次数(数量)');
            $table->integer('unit_id')->nullable()->comment('单位(仅限物品)');
            $table->string('specs')->nullable()->comment('规格');

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
        Schema::dropIfExists('customer_deposit_details');
    }
};
