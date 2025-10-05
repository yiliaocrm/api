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
        Schema::create('cashier_retail_detail', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('cashier_retail_id')->index()->comment('主单ID');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->string('type', 20)->comment('类别:goods、product');
            $table->integer('package_id')->nullable()->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->tinyInteger('splitable')->nullable()->comment('允许拆单(套餐用的)');
            $table->integer('product_id')->nullable()->comment('产品id');
            $table->string('product_name')->nullable()->comment('产品名称');
            $table->integer('goods_id')->nullable()->comment('物品id');
            $table->string('goods_name')->nullable()->comment('物品名称');
            $table->integer('times')->comment('使用次数');
            $table->integer('unit_id')->nullable()->comment('单位(仅限物品)');
            $table->string('specs')->nullable()->comment('规格');
            $table->decimal('price', 14, 4)->comment('项目原价');
            $table->decimal('sales_price', 14, 4)->comment('执行价格');
            $table->decimal('payable', 14, 4)->comment('成交价(应收金额)');
            $table->decimal('amount', 14, 4)->default(0)->comment('实收金额');
            $table->integer('department_id')->comment('结算科室(业绩归属)');
            $table->text('salesman')->comment('销售人员(允许多个)json格式');
            $table->text('remark')->nullable()->comment('备注');
            $table->integer('user_id')->comment('登记人员');
            $table->timestamps();
            $table->comment('零售收费明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_retail_detail');
    }
};
