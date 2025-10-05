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
        Schema::create('followup_template', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->comment('标题');
            $table->integer('type_id')->comment('类别');
            $table->string('keyword')->comment('搜索关键词');
            $table->integer('user_id')->comment('创建人');
            $table->timestamps();
            $table->comment('回访计划');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_template');
    }
};
