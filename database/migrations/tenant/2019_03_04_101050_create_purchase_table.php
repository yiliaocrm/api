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
        Schema::create('purchase', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->comment('单据号');
            $table->date('date')->comment('单据日期');
            $table->integer('warehouse_id')->comment('仓库');
            $table->integer('user_id')->comment('经办人');
            $table->integer('supplier_id')->comment('供应商');
            $table->string('supplier_name')->comment('供应商名称');
            $table->text('remark')->nullable()->comment('备注');
            $table->decimal('amount', 14, 4)->comment('采购总额');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：正常');
            $table->integer('check_user')->nullable()->comment('审核人员');
            $table->dateTime('check_time')->nullable()->comment('审核时间');
            $table->integer('create_user_id')->comment('录入人员');
            $table->integer('type_id')->comment('进货类别');
            $table->timestamps();
            $table->comment('进货入库主表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase');
    }
};
