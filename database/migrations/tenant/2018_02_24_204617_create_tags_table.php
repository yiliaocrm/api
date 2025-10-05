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
        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('标签名称');
            $table->smallInteger('parentid')->comment('父级标签');
            $table->tinyInteger('child')->default(0)->comment('是否有子级');
            $table->integer('order')->default(0)->comment('排序');
            $table->string('keyword')->nullable()->comment('搜索关键词');
            $table->string('tree')->nullable()->comment('树结构');
            $table->timestamps();
            $table->comment('标签表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
