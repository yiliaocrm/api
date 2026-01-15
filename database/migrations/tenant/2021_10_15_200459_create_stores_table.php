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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('门店名称');
            $table->string('short_name')->comment('门店简称');
            $table->string('phone')->nullable()->comment('联系电话');
            $table->string('address')->nullable()->comment('详细地址');
            $table->time('business_start')->default('09:00:00')->comment('营业时间开始');
            $table->time('business_end')->default('22:00:00')->comment('营业时间结束');
            $table->integer('slot_duration')->default(30)->comment('预约单位(分钟)');
            $table->json('appointment_color_config')->nullable()->comment('预约看板配色方案设置');
            $table->string('appointment_color_scheme')->default('default')->comment('预约看板颜色方案');
            $table->text('remark')->nullable()->comment('门店简介');
            $table->decimal('longitude', 10, 7)->nullable()->comment('经度');
            $table->decimal('latitude', 10, 7)->nullable()->comment('纬度');
            $table->timestamps();
            $table->comment('门店表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
