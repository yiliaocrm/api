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
        Schema::create('outpatient_prescription', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('reception_id')->index()->comment('分诊ID');
            $table->uuid('emr_id')->index()->comment('病历ID');
            $table->uuid('customer_id')->comment('顾客id');
            $table->integer('warehouse_id')->comment('发药仓库');
            $table->integer('department_id')->comment('处方科室');
            $table->integer('doctor_id')->comment('处方医生');
            $table->tinyInteger('type')->comment('处方类型(1、普通处方, 2、麻醉处方,3、精一,4、精二,5、中药,6、毒)');
            $table->text('diagnosis')->nullable()->comment('临床诊断');
            $table->decimal('amount', 14, 4)->default(0)->comment('处方价格');
            $table->integer('user_id')->comment('录单人员');
            $table->tinyInteger('status')->default(1)->comment('处方状态:1、未收费，2、收款未发药，3：已发药');
            $table->dateTime('charge_time')->nullable()->comment('收银时间');
            $table->dateTime('medicines_time')->nullable()->comment('发药时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('outpatient_prescription');
    }
};
