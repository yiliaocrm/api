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
        Schema::create('schedule_rule', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('班次');
            $table->time('start')->comment('上班时间');
            $table->time('end')->comment('下班时间');
            $table->string('color')->comment('颜色');
            $table->unsignedTinyInteger('onduty')->default(1)->comment('值班(可约)');
            $table->unsignedInteger('store_id')->comment('门店id');
            $table->timestamps();
            $table->comment('排班规则表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_rule');
    }
};
