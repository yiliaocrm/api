<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 库存批号
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('inventory_batchs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('goods_id')->comment('商品id');
            $table->string('goods_name')->comment('商品名称');
            $table->string('specs')->nullable()->comment('规格型号');
            $table->integer('warehouse_id')->comment('所在仓库');
            $table->decimal('price', 14, 4)->comment('单价');
            $table->decimal('number', 14, 4)->comment('剩余数量');
            $table->integer('unit_id')->comment('单位');
            $table->string('unit_name', 10)->comment('单位名称');
            $table->decimal('amount', 14, 4)->comment('成本');
            $table->integer('manufacturer_id')->nullable()->comment('生产厂家');
            $table->string('manufacturer_name')->nullable()->comment('生产厂家名称');
            $table->date('production_date')->nullable()->comment('生产日期');
            $table->date('expiry_date')->nullable()->comment('过期时间');
            $table->string('batch_code')->nullable()->comment('批号');
            $table->string('sncode')->nullable()->comment('SN码(串号、唯一序列号)');
            $table->text('remark')->nullable()->comment('备注');
            $table->string('batchable_type')->comment('批次创建业务(对应的model名)');
            $table->integer('batchable_id')->comment('批次创建业务对应的ID');
            $table->timestamps();
            $table->comment('库存批次表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_batchs');
    }
};
