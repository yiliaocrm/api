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
        Schema::create('cashier_arrearage', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('cashier_id')->index()->comment('关联收费中心id');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->tinyInteger('status')->default(1)->comment('单据状态(1、还款中。2、清讫、3、免单)');

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

            $table->uuid('table_id')->index()->comment('关联业务表ID(customer_product和customer_goods)，还款后需要更新记录');

            // 财务信息
            $table->decimal('payable', 14, 4)->comment('本单应收金额');
            $table->decimal('income', 14, 4)->comment('本单实收金额');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->decimal('amount', 14, 4)->default(0)->comment('累计还款金额');
            $table->decimal('leftover', 14, 4)->default(0)->comment('尚欠金额');
            $table->text('salesman')->nullable()->comment('销售人员(允许多个)json格式');
            $table->integer('department_id')->comment('结算科室(业绩归属)');

            $table->dateTime('last_repayment_time')->nullable()->comment('最近还款时间');
            $table->integer('user_id')->comment('结单人员');
            $table->timestamps();
            $table->comment('收费中心欠款表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_arrearage');
    }
};
