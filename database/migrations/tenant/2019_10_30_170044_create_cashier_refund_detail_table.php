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
        Schema::create('cashier_refund_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('status')->comment('状态(1:待审核、2:待收费、3:已收费、4:退单)');
            $table->uuid('cashier_refund_id')->comment('退款ID');
            $table->uuid('cashier_id')->index()->nullable()->comment('收费单号');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->uuid('customer_product_id')->nullable()->comment('顾客项目明细单id');
            $table->uuid('customer_goods_id')->nullable()->comment('顾客物品明细表id');


            $table->integer('package_id')->nullable()->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->integer('product_id')->nullable()->comment('产品id');
            $table->string('product_name')->nullable()->comment('产品名称');
            $table->integer('goods_id')->nullable()->comment('物品id');
            $table->string('goods_name')->nullable()->comment('物品名称');
            $table->integer('times')->comment('退款次数(数量)');
            $table->integer('unit_id')->nullable()->comment('单位(仅限物品)');
            $table->string('specs')->nullable()->comment('规格');

            $table->integer('department_id')->comment('结算科室(业绩归属)');
            $table->decimal('amount', 14, 4)->comment('退款金额');
            $table->text('salesman')->nullable()->comment('销售人员(允许多个)json格式');
            $table->integer('user_id')->comment('收银人员');
            $table->integer('cashier_user_id')->nullable()->comment('收银人员');
            $table->text('remark')->nullable()->comment('退款备注');
            $table->timestamps();
            $table->comment('退款申请明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_refund_detail');
    }
};
