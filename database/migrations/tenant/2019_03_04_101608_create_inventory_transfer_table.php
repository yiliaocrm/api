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
        Schema::create('inventory_transfer', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->comment('调拨单号');
            $table->date('date')->comment('单据日期');
            $table->integer('out_warehouse_id')->comment('出库仓库Id');
            $table->integer('in_warehouse_id')->comment('入库仓库Id');
            $table->decimal('amount', 14, 4)->comment('调拨成本');
            $table->text('remark')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：审核通过');
            $table->integer('user_id')->comment('经办人员');
            $table->integer('check_user')->nullable()->comment('审核人员');
            $table->dateTime('check_time')->nullable()->comment('审核时间');
            $table->integer('create_user_id')->comment('录入人员');
            $table->timestamps();
            $table->comment('库存调拨表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer');
    }
};
