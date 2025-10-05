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
        Schema::create('failure', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('parentid');
            $table->tinyInteger('child')->default(0);
            $table->integer('order')->default(0);
            $table->string('keyword')->nullable();
            $table->string('tree')->nullable();
            $table->timestamps();
            $table->comment('未成交原因表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('failure');
    }
};
