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
        Schema::create('inventory_check_details', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('单据号');
            $table->date('date')->comment('单据日期');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：审核通过');
            $table->integer('inventory_checks_id')->comment('盘点主单ID');
            $table->integer('warehouse_id')->comment('盘点仓库id');
            $table->integer('goods_id')->comment('商品id');
            $table->string('goods_name')->comment('商品名称');
            $table->string('specs')->nullable()->comment('型号规格');
            $table->timestamps();
            $table->comment('库存盘点明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_check_details');
    }
};
