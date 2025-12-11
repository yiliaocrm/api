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
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('操作用户ID');
            $table->string('ip', 45)->nullable()->comment('IP地址');
            $table->string('method', 10)->comment('请求方法');
            $table->string('controller')->nullable()->comment('控制器');
            $table->string('action')->nullable()->comment('控制器方法');
            $table->text('url')->nullable()->comment('完整URL');
            $table->json('params')->nullable()->comment('请求参数');
            $table->integer('status_code')->nullable()->comment('HTTP状态码');
            $table->decimal('duration', 8, 2)->nullable()->comment('执行时长(秒)');
            $table->text('user_agent')->nullable()->comment('User Agent');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
            $table->index('method');
            $table->index('ip');
            $table->index('controller');
            $table->index('action');
            $table->comment('操作日志表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
    }
};
