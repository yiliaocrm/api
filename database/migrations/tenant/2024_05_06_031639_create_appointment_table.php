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
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('store_id')->default(1)->index()->comment('门店id');
            $table->uuid('customer_id')->index();
            $table->uuid('reservation_id')->nullable()->comment('关联网电咨询记录');
            $table->uuid('reception_id')->index()->nullable()->comment('关联分诊记录');
            $table->timestamp('reception_time')->nullable()->comment('分诊时间');
            $table->timestamp('arrival_time')->nullable()->comment('顾客上门报道时间');
            $table->string('type', 10)->nullable()->comment('预约类型(coming:面诊预约,treatment:治疗预约,operation:手术预约)');
            $table->date('date')->comment('预约日期');
            $table->dateTime('start')->comment('开始时间');
            $table->dateTime('end')->comment('结束时间');
            $table->unsignedInteger('duration')->comment('持续时间(时长)');
            $table->unsignedTinyInteger('status')->comment('预约状态(0:待确认,1:待上门,2:已到店,3:已接待,4:已收费,5:已治疗,6:已超时,7:已离开,8:已取消');
            $table->string('items')->nullable()->comment('预约项目(多个项目用逗号分隔)');
            $table->string('items_name')->nullable()->comment('预约项目');
            $table->unsignedInteger('department_id')->nullable()->comment('预约科室');
            $table->unsignedInteger('doctor_id')->nullable()->comment('预约医生');
            $table->unsignedInteger('consultant_id')->nullable()->comment('预约顾问');
            $table->unsignedInteger('technician_id')->nullable()->comment('预约技师(皮肤科专用)');
            $table->string('anaesthesia')->nullable()->comment('麻醉方式(regional:局麻,general:全麻)');
            $table->unsignedInteger('room_id')->nullable()->comment('预约诊室');
            $table->unsignedInteger('create_user_id')->comment('录单人员');
            $table->text('remark')->nullable()->comment('预约备注');
            $table->timestamps();
            $table->comment('预约表');
        });
        Schema::create('appointment_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_type')->comment('配置类型（consultant, doctor, technician, department, room）');
            $table->unsignedInteger('target_id')->comment('目标ID，根据类型，可能是user_id, department_id, 或 room_id');
            $table->unsignedInteger('store_id')->comment('所属门店ID');
            $table->unsignedInteger('order')->default(0)->comment('用于排序');
            $table->tinyInteger('display')->default(1)->comment('是否显示');
            $table->timestamps();
            $table->comment('预约配置表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('appointment_configs');
    }
};
