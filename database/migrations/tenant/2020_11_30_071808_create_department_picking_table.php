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
        Schema::create('department_picking', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('领料单号');
            $table->date('date')->comment('领料日期');
            $table->unsignedInteger('type_id')->comment('领料类别');
            $table->integer('warehouse_id')->comment('出料仓库');
            $table->integer('department_id')->comment('领料科室');
            $table->integer('user_id')->comment('领料人员');
            $table->integer('create_user_id')->comment('录单人员');
            $table->decimal('amount', 14, 4)->comment('领料成本');
            $table->text('remark')->nullable()->comment('领料备注');
            $table->tinyInteger('status')->default(1)->comment('状态 1：草稿、2：审核通过');
            $table->integer('check_user')->nullable()->comment('审核人员');
            $table->dateTime('check_time')->nullable()->comment('审核时间');
            $table->timestamps();
            $table->comment('科室领料单表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('department_picking');
    }
};
