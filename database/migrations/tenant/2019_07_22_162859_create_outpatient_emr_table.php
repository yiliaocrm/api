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
        Schema::create('outpatient_emr', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reception_id')->index()->comment('分诊ID');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->date('illness_date')->comment('发病日期');
            $table->string('chief_complaint')->comment('主诉');
            $table->string('present_history')->nullable()->comment('现病史');
            $table->string('past_history')->nullable()->comment('既往史');
            $table->string('diagnosis')->comment('初步诊断');
            $table->integer('user_id')->comment('录单人员id');
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
        Schema::dropIfExists('outpatient_emr');
    }
};
