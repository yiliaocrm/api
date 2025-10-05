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
        Schema::create('goods_type', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->smallInteger('parentid');
            $table->string('type', 20)->default('goods')->comment('表的类型:goods物品,drug药品');
            $table->tinyInteger('editable')->default(1)->comment('是否允许编辑');
            $table->tinyInteger('deleteable')->default(1)->comment('是否允许删除');
            $table->tinyInteger('child')->default(0);
            $table->integer('order')->default(0)->comment('排序');
            $table->string('keyword')->nullable();
            $table->string('tree')->nullable();
            $table->comment('商品分类表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_type');
    }
};
