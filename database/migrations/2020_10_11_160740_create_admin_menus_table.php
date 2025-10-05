<?php

use App\Models\AdminMenu;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->integer('parentid')->comment('上级菜单');
            $table->string('name')->comment('组件名称');
            $table->string('title')->comment('菜单名称');
            $table->string('path')->comment('访问路径');
            $table->text('meta')->comment('菜单元数据');
            $table->string('component')->nullable()->comment('组件路径');
            $table->tinyInteger('child')->default(0);
            $table->string('permission')->nullable()->comment('权限名称');
            $table->integer('order')->default(0)->comment('排序');
            $table->string('keyword')->nullable()->comment('关键字');
            $table->string('remark')->nullable()->comment('备注');
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
        Schema::dropIfExists('admin_menus');
    }
}
