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
        Schema::create('whitelists', function (Blueprint $table) {
            $table->id();
            $table->ipAddress('start_ip')->comment('开始IP');
            $table->ipAddress('end_ip')->comment('结束IP');
            $table->string('description')->nullable()->comment('描述字段');
            $table->timestamps();
            $table->comment('IP白名单表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('whitelists');
    }
};
