<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * 欠款(还款)明细表
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('cashier_arrearage_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('cashier_arrearage_id')->comment('欠款单id');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->uuid('cashier_id')->index()->comment('收费单号');

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

            $table->decimal('income', 14, 4)->comment('实收金额');
            $table->text('remark')->nullable()->comment('还款备注');
            $table->text('salesman')->nullable()->comment('销售人员(允许多个)json格式');
            $table->integer('department_id')->comment('结算科室(业绩归属)');
            $table->integer('user_id')->comment('结单人员');
            $table->timestamps();
            $table->comment('收费中心欠款明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_arrearage_detail');
    }
};
