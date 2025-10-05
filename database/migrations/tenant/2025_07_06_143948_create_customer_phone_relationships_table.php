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
        Schema::create('customer_phone_relationships', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('关系名称, 例如: 本人, 配偶, 父亲');
            $table->boolean('system')->default(false)->comment('是否系统配置');
            $table->timestamps();
            $table->comment('顾客电话关系字典表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_phone_relationships');
    }
};
