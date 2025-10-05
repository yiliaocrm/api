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
        Schema::create('diagnosis_category', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->comment('分类名称');
            $table->smallInteger('parentid');
            $table->tinyInteger('child')->default(0);
            $table->string('keyword')->nullable();
            $table->string('tree')->nullable();
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
        Schema::dropIfExists('diagnosis_category');
    }
};
