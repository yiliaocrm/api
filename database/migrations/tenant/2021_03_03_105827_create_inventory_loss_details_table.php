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
        Schema::create('inventory_loss_details', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('单据号');
            $table->date('date')->comment('单据日期');
            $table->integer('inventory_loss_id')->comment('报损单ID');
            $table->integer('warehouse_id')->comment('出料仓库id');
            $table->integer('department_id')->comment('领料科室');
            $table->integer('goods_id')->comment('商品id');
            $table->string('goods_name')->comment('商品名称');
            $table->string('specs')->nullable()->comment('型号规格');
            $table->integer('manufacturer_id')->nullable()->comment('生产厂家ID');
            $table->string('manufacturer_name')->nullable()->comment('生产厂家名称');
            $table->integer('inventory_batchs_id')->comment('库存批次ID');
            $table->string('batch_code')->comment('库存批号');
            $table->date('production_date')->nullable()->comment('生产日期');
            $table->date('expiry_date')->nullable()->comment('过期时间');
            $table->integer('unit_id')->comment('商品单位');
            $table->string('unit_name', 10)->comment('商品单位名称');
            $table->decimal('price', 14, 4)->comment('单价');
            $table->decimal('number', 14, 4)->comment('商品数量');
            $table->decimal('amount', 14, 4)->comment('商品总价');
            $table->string('sncode')->nullable()->comment('SN码(串号、唯一序列号)');
            $table->text('remark')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：审核通过');
            $table->timestamps();
            $table->comment('库存报损单明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_loss_details');
    }
};
