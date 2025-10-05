<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_phone_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index()->comment('员工ID');
            $table->uuid('customer_id')->index()->comment('顾客ID');
            $table->string('phone', 50)->comment('查看的电话号码');
            $table->timestamps();
            $table->comment('顾客号码查看记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_phone_views');
    }
};
