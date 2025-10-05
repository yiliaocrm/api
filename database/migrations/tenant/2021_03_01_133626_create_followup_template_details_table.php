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
        Schema::create('followup_template_details', function (Blueprint $table) {
            $table->id();
            $table->integer('followup_template_id')->unsigned()->comment('回访模板id');
            $table->integer('followup_type_id')->unsigned()->comment('回访类型');
            $table->integer('day')->unsigned()->comment('间隔天数');
            $table->string('title')->comment('回访主题');
            $table->string('followup_role')->nullable()->comment('回访角色');
            $table->integer('user_id')->unsigned()->nullable()->comment('指定人员');
            $table->timestamps();
            $table->comment('回访计划明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_template_details');
    }
};
