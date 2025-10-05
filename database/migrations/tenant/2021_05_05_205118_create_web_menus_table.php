<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('web_menus', function (Blueprint $table) {
            $table->id();
            $table->integer('parentid')->comment('上级菜单ID');
            $table->tinyInteger('child')->default(0)->comment('是否有子节点');
            $table->string('type', 5)->default('web')->comment('菜单类型(web,app)');
            $table->integer('order')->default(0);
            $table->string('name')->comment('菜单名称');
            $table->string('path')->nullable()->comment('路径');
            $table->text('meta')->nullable()->comment('meta配置');
            $table->string('component')->nullable()->comment('组件');
            $table->string('icon')->nullable()->comment('图标');
            $table->string('route')->nullable()->comment('路由名称');
            $table->string('url')->nullable()->comment('链接地址');
            $table->string('permission')->default('')->comment('权限名称');
            $table->tinyInteger('display')->default(1)->comment('是否显示');
            $table->string('remark')->nullable()->comment('备注');
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
        Schema::dropIfExists('web_menus');
    }
};
