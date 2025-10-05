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
        Schema::create('prescription_ways', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->tinyInteger('type')->default(1);    // 类型:1、西药 2、中药
            $table->string('keyword');                  // 搜索关键词
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
        Schema::dropIfExists('prescription_ways');
    }
};
