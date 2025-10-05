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
        Schema::create('inventory_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inventory_batchs_id')->comment('库存批次ID');
            $table->string('key')->comment('单据号');
            $table->date('date')->comment('单据日期');
            $table->integer('warehouse_id')->comment('仓库id');
            $table->integer('goods_id')->comment('商品id');
            $table->string('goods_name')->comment('商品名称');
            $table->string('specs')->nullable()->comment('型号规格');
            $table->decimal('price', 14, 4)->comment('单价');
            $table->decimal('number', 14, 4)->comment('数量');
            $table->decimal('amount', 14, 4)->comment('总价');
            $table->integer('unit_id')->comment('最小单位');
            $table->string('unit_name', 10)->comment('单位名称');
            $table->integer('manufacturer_id')->nullable()->comment('生产厂家');
            $table->string('manufacturer_name')->nullable()->comment('生产厂家名称');
            $table->date('production_date')->nullable()->comment('生产日期');
            $table->date('expiry_date')->nullable()->comment('过期时间');
            $table->string('batch_code')->nullable()->comment('批号');
            $table->string('sncode')->nullable()->comment('SN码(串号、唯一序列号)');
            $table->text('remark')->nullable()->comment('备注');
            $table->decimal('batchs_number', 14, 4)->comment('批次库存数量(结存)');
            $table->decimal('batchs_amount', 14, 4)->comment('批次库存成本(结存)');
            $table->decimal('inventory_number', 14, 4)->comment('总库存数量(结存)');
            $table->decimal('inventory_amount', 14, 4)->comment('总库存成本(结存)');
            $table->string('detailable_type');
            $table->integer('detailable_id');
            $table->timestamps();
            $table->comment('商品库存变动明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_detail');
    }
};
