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
        Schema::create('sms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id')->nullable()->comment('短信模板ID');
            $table->string('phone')->comment('接收手机号');
            $table->text('content')->comment('短信内容');
            $table->string('channel')->comment('短信通道');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->comment('发送状态: pending-等待发送, sent-发送成功, failed-发送失败');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->json('gateway_response')->nullable()->comment('网关响应数据');
            $table->unsignedBigInteger('user_id')->default(0)->comment('发送人员');
            $table->string('scenario')->comment('使用场景标识');
            $table->string('scenario_id')->comment('使用场景业务ID');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamps();
            $table->comment('短信发送记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms');
    }
};
