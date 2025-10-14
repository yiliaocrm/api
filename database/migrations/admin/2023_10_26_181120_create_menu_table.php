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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->integer('parentid')->comment('上级菜单');
            $table->string('name')->nullable()->comment('组件名称');
            $table->string('title')->comment('菜单名称');
            $table->string('path')->comment('访问路径');
            $table->text('meta')->comment('菜单元数据');
            $table->string('component')->nullable()->comment('组件路径');
            $table->string('type', 5)->default('web')->comment('菜单类型(web,app)');
            $table->string('menu_type')->default('menu')->comment('菜单类别(directory,menu,button)');
            $table->tinyInteger('child')->default(0);
            $table->string('permission')->nullable()->comment('权限名称');
            $table->json('permission_scope')->nullable()->comment('数据权限范围');
            $table->integer('order')->default(0)->comment('排序');
            $table->string('keyword')->nullable()->comment('关键字');
            $table->string('remark')->nullable()->comment('备注');
            $table->string('tree')->nullable();
            $table->timestamps();
            $table->comment('菜单表');
        });
        Schema::create('menu_permission_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('权限名称');
            $table->string('slug')->unique()->comment('权限标识');
            $table->integer('order')->default(0)->comment('排序(越靠前权限越小)');
            $table->string('component')->nullable()->comment('前端组件');
            $table->string('component_params')->nullable()->comment('前端组件参数');
            $table->timestamps();
            $table->comment('菜单权限范围表基础信息表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
        Schema::dropIfExists('menu_permission_scopes');
    }
};
