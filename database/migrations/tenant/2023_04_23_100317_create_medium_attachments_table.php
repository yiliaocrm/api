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
        Schema::create('medium_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('medium_id')->comment('渠道ID');
            $table->string('name')->comment('图片名称');
            $table->string('thumb')->comment('缩略图');
            $table->string('file_path')->comment('文件路径');
            $table->string('file_mime')->nullable()->comment('文件类型');
            $table->unsignedInteger('create_user_id')->comment('创建人员');
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
        Schema::dropIfExists('medium_attachments');
    }
};
