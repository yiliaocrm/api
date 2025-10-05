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
        Schema::create('attachments', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 业务类型
            $table->uuid('model_id');
            $table->string('model_type');
            $table->index(['model_id', 'model_type']);

            $table->string('disk')->comment('硬盘');
            $table->string('file_name')->comment('文件名');
            $table->string('file_path')->comment('文件路径');
            $table->integer('file_size')->unsigned()->comment('文件大小');
            $table->string('file_ext', 10)->comment('文件扩展名');
            $table->string('file_mime')->nullable();
            $table->tinyInteger('isimage')->default(0)->comment('是否为图片');
            $table->tinyInteger('isthumb')->default(0)->comment('是否缩略图');
            $table->integer('user_id')->nullable()->comment('用户id');
            $table->string('ip', 16)->comment('上传ip');

            $table->nullableTimestamps();
            $table->comment('附件表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
