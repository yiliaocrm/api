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
        Schema::create('recharge', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->uuid('cashier_id')->nullable()->comment('收费单号');
            $table->decimal('balance', 14, 4)->default(0)->comment('充值金额');
            $table->integer('medium_id')->comment('媒介来源');
            $table->tinyInteger('type')->comment('接诊类型(初诊、复诊..)');
            $table->integer('department_id')->comment('结算科室(业绩归属)');
            $table->text('salesman')->nullable()->comment('销售人员(允许多个)json格式');
            $table->text('remark')->nullable()->comment('备注信息');
            $table->integer('user_id')->comment('录单人员');
            $table->timestamps();
            $table->comment('充值记录表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('recharge');
    }
};
