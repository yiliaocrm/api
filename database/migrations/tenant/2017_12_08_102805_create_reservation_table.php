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
        Schema::create('reservation', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('customer_id')->index();
            $table->tinyInteger('status')->comment('状态');
            $table->integer('type')->comment('受理类型');
            $table->string('items')->comment('咨询项目');
            $table->date('date')->comment('受理日期');
            $table->dateTime('time')->nullable()->comment('预约来院时间');
            $table->integer('department_id')->comment('咨询科室');
            $table->integer('medium_id')->comment('媒介来源');
            $table->integer('ascription')->comment('咨询人员');
            $table->integer('user_id')->comment('录入人');
            $table->uuid('reception_id')->nullable()->comment('接待id');
            $table->text('remark')->nullable()->comment('备注');
            $table->dateTime('cometime')->nullable()->comment('上门时间');
            $table->timestamps();
            $table->comment('网电咨询相关表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation');
    }
};
