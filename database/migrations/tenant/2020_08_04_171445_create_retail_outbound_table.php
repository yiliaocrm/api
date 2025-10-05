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
        Schema::create('retail_outbound', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->comment('出料单号');
            $table->date('date')->comment('单据日期');
            $table->uuid('customer_id')->comment('顾客id');
            $table->decimal('amount', 14, 4)->comment('出库总(零售)金额');
            $table->integer('department_id')->comment('出料科室');
            $table->integer('warehouse_id')->comment('出料仓库');
            $table->text('remark')->nullable()->comment('备注');
            $table->integer('user_id')->comment('出料人员');
            $table->integer('create_user_id')->comment('录入人员');
            $table->timestamps();
            $table->comment('零售出料表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('retail_outbound');
    }
};
