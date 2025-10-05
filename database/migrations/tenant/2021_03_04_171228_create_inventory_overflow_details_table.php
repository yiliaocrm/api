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
        Schema::create('inventory_overflow_details', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('单据号');
            $table->date('date')->comment('单据日期');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：审核通过');
            $table->integer('inventory_overflow_id')->comment('报溢单ID');
            $table->integer('warehouse_id')->comment('仓库id');
            $table->integer('goods_id')->comment('商品id');
            $table->string('goods_name')->comment('商品名称');
            $table->string('specs')->nullable()->comment('规格型号');
            $table->decimal('price', 14, 4)->comment('单价');
            $table->decimal('number', 14, 4)->comment('进货数量');
            $table->integer('unit_id')->comment('进货单位');
            $table->string('unit_name', 10)->comment('进货单位名称');
            $table->decimal('amount', 14, 4)->comment('总价');
            $table->integer('manufacturer_id')->nullable()->comment('生产厂家ID');
            $table->string('manufacturer_name')->nullable()->comment('生产厂家名称');
            $table->date('production_date')->nullable()->comment('生产日期');
            $table->date('expiry_date')->nullable()->comment('过期时间');
            $table->string('batch_code')->nullable()->comment('批号');
            $table->string('sncode')->nullable()->comment('SN码(串号、唯一序列号)');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->comment('报溢单明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_overflow_details');
    }
};
