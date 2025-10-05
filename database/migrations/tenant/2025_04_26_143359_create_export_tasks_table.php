<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('export_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('任务名称');
            $table->string('hash')->nullable()->comment('任务hash,通过md5(params)生成');
            $table->string('status')->default('pending')->comment('任务状态: pending:待处理, processing:处理中, completed:完成, failed:失败, expired:文件过期');
            $table->json('params')->nullable()->comment('导出参数');
            $table->string('file_path')->nullable()->comment('文件路径');
            $table->longText('error_message')->nullable()->comment('错误信息');
            $table->timestamp('failed_at')->nullable()->comment('失败时间');
            $table->timestamp('started_at')->nullable()->comment('开始时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->unsignedBigInteger('user_id')->comment('创建人员');
            $table->timestamps();
            $table->comment('导出任务表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_tasks');
    }
};
