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
        Schema::create('purchase_return', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('单据号');
            $table->date('date')->comment('单据日期');
            $table->integer('user_id')->comment('经办人');
            $table->integer('warehouse_id')->comment('仓库');
            $table->integer('supplier_id')->comment('退货厂商id');
            $table->string('supplier_name')->comment('退货厂商名称');
            $table->text('remark')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：审核通过');
            $table->integer('check_user')->nullable()->comment('审核人员');
            $table->dateTime('check_time')->nullable()->comment('审核时间');
            $table->decimal('amount', 14, 4)->comment('退货总额');
            $table->integer('create_user_id')->comment('录单人员');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return');
    }
};
