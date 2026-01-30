<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_templates', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->nullable()->comment('模板图标');
            $table->string('title')->comment('名称');
            $table->string('template')->comment('导入模板路径');
            $table->integer('chunk_size')->comment('分块读入数量')->default(10);
            $table->integer('async_limit')->default(0)->comment('大于等于 n 启用异步');
            $table->string('use_import')->comment('使用的导入类');
            $table->unsignedInteger('create_user_id')->default(1)->comment('创建人');
            $table->timestamps();
            $table->comment('导入文件模板');
        });
        Schema::create('import_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('template_id')->default(1)->comment('导入模板');
            $table->json('import_header')->comment('导入表头数据');
            $table->string('file_name')->comment('文件名');
            $table->string('file_path')->comment('文件路径');
            $table->string('file_size')->comment('文件大小');
            $table->string('file_type')->comment('文件类型');
            $table->tinyInteger('status')->comment('状态:0=未导入,1=导入中,2=导入完成');
            $table->integer('total_rows')->comment('总行数')->default(0);
            $table->integer('success_rows')->comment('成功导入行数')->default(0);
            $table->integer('fail_rows')->comment('未导入行数')->default(0);
            $table->unsignedInteger('create_user_id')->default(1)->comment('创建人');
            $table->timestamps();
            $table->comment('导入任务表');
        });
        Schema::create('import_task_details', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id')->comment('导入任务');
            $table->json('row_data')->comment('行数据');
            $table->tinyInteger('status')->comment('状态:0=未导入,1=成功,2=失败');
            $table->string('error_msg')->nullable()->comment('错误信息');
            $table->timestamps();
            $table->comment('导入任务明细表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('import_templates');
        Schema::drop('import_tasks');
        Schema::drop('import_task_details');
    }
};
