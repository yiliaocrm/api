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
            $table->uuid('appointment_id')->nullable()->index()->comment('预约id');
            $table->integer('department_id')->comment('分诊科室');
            $table->string('items')->comment('咨询项目');
            $table->tinyInteger('type')->comment('接诊类型');
            $table->tinyInteger('status')->comment('成交状态（1:未成交、2:成交）');
            $table->integer('consultant')->nullable()->comment('销售顾问');
            $table->integer('reception')->nullable()->comment('分诊接待人员');
            $table->integer('user_id')->comment('录入人员');
            $table->integer('ek_user')->nullable()->comment('二开人员');
            $table->integer('doctor')->nullable()->comment('接诊医生');
            $table->integer('medium_id')->comment('媒介来源');
            $table->integer('receptioned')->default(0)->comment('是否接待');
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
