<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_check_details', function (Blueprint $table) {
            $table->id();
            $table->integer('inventory_check_id')->comment('盘点主单ID');
            $table->string('key')->comment('单据号');
            $table->date('date')->comment('单据日期');
            $table->integer('warehouse_id')->comment('盘点仓库');
            $table->integer('goods_id')->comment('商品ID');
            $table->string('goods_name')->comment('商品名称');
            $table->string('specs')->nullable()->comment('规格型号');
            $table->integer('manufacturer_id')->nullable()->comment('生产厂家ID');
            $table->string('manufacturer_name')->nullable()->comment('生产厂家');
            $table->integer('inventory_batchs_id')->nullable()->comment('库存批次ID');
            $table->string('batch_code')->comment('批号');
            $table->date('production_date')->nullable()->comment('生产日期');
            $table->date('expiry_date')->nullable()->comment('失效日期');
            $table->string('sncode')->nullable()->comment('SN码');
            $table->integer('unit_id')->nullable()->comment('单位ID');
            $table->string('unit_name')->nullable()->comment('单位名称');
            $table->decimal('book_number', 14, 4)->default(0)->comment('账面数量');
            $table->decimal('actual_number', 14, 4)->default(0)->comment('实盘数量');
            $table->decimal('diff_number', 14, 4)->default(0)->comment('差异数量');
            $table->decimal('price', 14, 4)->default(0)->comment('价格');
            $table->decimal('diff_amount', 14, 4)->default(0)->comment('差异金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：正常');
            $table->timestamps();
            $table->comment('库存盘点明细表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_check_details');
    }
};
