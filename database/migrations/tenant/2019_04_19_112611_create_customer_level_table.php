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
        Schema::create('customer_level', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->comment('会员等级名称');
            $table->timestamps();
            $table->comment('顾客会员等级表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_level');
    }
};
