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
        Schema::create('consultant_remark_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('标题');
            $table->text('content')->comment('模板内容');
            $table->integer('create_user_id')->unsigned()->comment('创建人员');
            $table->timestamps();
            $table->comment('顾问备注模板表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('consultant_remark_templates');
    }
};
