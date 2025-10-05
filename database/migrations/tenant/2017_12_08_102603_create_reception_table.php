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
        Schema::create('reception', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->integer('department_id')->comment('咨询科室');
            $table->string('items')->comment('咨询项目');
            $table->tinyInteger('type')->comment('接诊类型(初诊、复诊..)');
            $table->tinyInteger('status')->comment('状态（0:未保存、1:未成交、2:成交）');
            $table->integer('consultant')->nullable()->comment('现场咨询');
            $table->integer('reception')->nullable()->comment('分诊接待人员');
            $table->integer('user_id')->comment('录入人员');
            $table->integer('ek_user')->nullable()->comment('二开人员');
            $table->integer('doctor')->nullable()->comment('接诊医生');
            $table->integer('medium_id')->comment('媒介来源');
            $table->integer('receptioned')->default(0)->comment('咨询师/医生 是否接诊了');
            $table->integer('failure_id')->nullable()->comment('未成交原因');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->comment('分诊接待表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('reception');
    }
};
