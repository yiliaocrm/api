<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_login_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Banner 标题');
            $table->string('image_path')->comment('图片路径');
            $table->string('link_url')->nullable()->comment('点击跳转链接');
            $table->integer('order')->default(0)->comment('排序权重（数字越小越靠前）');
            $table->boolean('disabled')->default(false)->comment('是否禁用（0启用/1禁用）');
            $table->timestamps();
            $table->comment('租户登录页配置表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_login_banners');
    }
};
