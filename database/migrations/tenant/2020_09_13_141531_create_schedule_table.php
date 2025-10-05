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
        Schema::create('schedule', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('班次');
            $table->dateTime('start')->comment('上班时间');
            $table->dateTime('end')->comment('下班时间');
            $table->string('color')->comment('背景色');
            $table->unsignedTinyInteger('onduty')->default(1)->comment('是否值班(是否可约)');
            $table->unsignedInteger('user_id')->comment('员工id');
            $table->unsignedInteger('store_id')->comment('门店ID');
            $table->timestamps();
            $table->comment('排班表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule');
    }
};
