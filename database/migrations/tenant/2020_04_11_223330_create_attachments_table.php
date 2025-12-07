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
            $table->id();
            $table->unsignedBigInteger('group_id')->nullable()->index()->comment('分组ID');
            $table->string('disk')->comment('存储磁盘');
            $table->string('file_name')->comment('文件名');
            $table->string('file_path')->comment('文件路径');
            $table->unsignedInteger('file_size')->comment('文件大小(字节)');
            $table->string('file_ext', 10)->comment('文件后缀');
            $table->string('file_mime')->nullable()->comment('MIME类型');
            $table->string('file_md5', 32)->unique()->index()->comment('文件MD5值');
            $table->boolean('is_image')->default(false)->index()->comment('是否为图片');
            $table->unsignedInteger('download_count')->default(0)->comment('下载次数');
            $table->unsignedInteger('reference_count')->default(0)->comment('引用次数');
            $table->timestamp('last_used_at')->nullable()->comment('最后使用时间');
            $table->unsignedInteger('user_id')->nullable()->comment('上传用户ID');
            $table->string('ip', 45)->comment('上传IP');
            $table->timestamps();
            $table->index('created_at');
            $table->comment('附件表');
        });

        Schema::create('attachment_uses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attachment_id')->index()->comment('附件ID');
            $table->uuidMorphs('usable');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->comment('附件引用表');
        });

        Schema::create('attachment_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('分组名称');
            $table->unsignedBigInteger('parent_id')->nullable()->index()->comment('父级ID');
            $table->string('tree', 255)->default('')->index()->comment('树路径');
            $table->string('keyword', 50)->nullable()->comment('关键字');
            $table->unsignedInteger('order')->default(0)->index()->comment('排序');
            $table->tinyInteger('system')->default(0)->comment('系统分组(不可删除)');
            $table->timestamps();
            $table->comment('附件分组表');
        });

        Schema::create('attachment_thumbnails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attachment_id')->index()->comment('附件ID');
            $table->unsignedInteger('width')->comment('宽度');
            $table->unsignedInteger('height')->comment('高度');
            $table->string('disk')->comment('存储磁盘');
            $table->string('file_path')->comment('文件路径');
            $table->unsignedInteger('file_size')->comment('文件大小(字节)');
            $table->string('file_ext', 10)->default('jpg')->comment('文件后缀');
            $table->timestamps();
            $table->comment('附件缩略图表');
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
        Schema::dropIfExists('attachment_uses');
        Schema::dropIfExists('attachment_groups');
        Schema::dropIfExists('attachment_thumbnails');
    }
};