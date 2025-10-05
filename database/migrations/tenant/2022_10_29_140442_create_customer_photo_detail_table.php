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
        Schema::create('customer_photo_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_photo_id')->comment('相册id');
            $table->uuid('customer_id')->comment('顾客id');
            $table->string('name')->comment('图片名称');
            $table->string('thumb')->comment('缩略图');
            $table->string('file_path')->comment('文件路径');
            $table->string('file_mime')->nullable()->comment('文件类型');
            $table->integer('create_user_id')->comment('创建人员');
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
        Schema::dropIfExists('customer_photo_details');
    }
};
