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
        Schema::create('sms_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('场景名称');
            $table->string('scenario', 50)->unique()->comment('场景标识');
            $table->json('variables')->nullable()->comment('可用变量JSON格式');
            $table->timestamps();
            $table->comment('短信使用场景表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_scenarios');
    }
};
