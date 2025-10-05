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
        // 项目套餐分类
        Schema::create('product_package_type', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->smallInteger('parentid');
            $table->integer('order')->default(0);
            $table->tinyInteger('child')->default(0);
            $table->string('keyword')->nullable();
            $table->string('tree')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_package_type');
    }
};
