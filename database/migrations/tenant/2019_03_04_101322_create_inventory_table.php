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
        Schema::create('inventory', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('warehouse_id')->index()->comment('仓库id');
            $table->integer('goods_id')->index()->comment('商品id');
            $table->decimal('number', 14, 4)->default(0.00)->comment('库存数量（转换成最小单位数量）');
            $table->decimal('amount', 14, 4)->default(0.00)->comment('库存成本');
            $table->timestamps();
            $table->comment('商品库存（实时库存、需要单位转换）');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
