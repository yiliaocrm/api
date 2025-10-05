<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('qufriends', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('亲友关系名称');
            $table->timestamps();
            $table->comment('亲友关系');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('qufriends');
    }
};
