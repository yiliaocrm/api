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
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->comment('短信模板分类id');
            $table->string('name')->comment('模板名称');
            $table->string('code')->comment('模板编码');
            $table->text('content')->comment('短信内容');
            $table->unsignedBigInteger('scenario_id')->comment('使用场景ID');
            $table->string('channel')->comment('短信通道');
            $table->boolean('disabled')->default(false)->comment('是否禁用');
            $table->timestamps();
            $table->comment('短信模板表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
