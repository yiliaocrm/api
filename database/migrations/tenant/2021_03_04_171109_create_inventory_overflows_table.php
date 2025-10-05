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
        Schema::create('inventory_overflows', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('单据号');
            $table->date('date')->comment('单据日期');
            $table->integer('warehouse_id')->comment('仓库');
            $table->integer('department_id')->comment('所属科室');
            $table->integer('user_id')->comment('经办人员');
            $table->text('remark')->nullable()->comment('备注');
            $table->decimal('amount', 14, 4)->comment('报溢总额');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：审核通过');
            $table->integer('check_user')->nullable()->comment('审核人员');
            $table->dateTime('check_time')->nullable()->comment('审核时间');
            $table->integer('create_user_id')->comment('录入人员');
            $table->timestamps();
            $table->comment('库存报溢单据主表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_overflows');
    }
};
