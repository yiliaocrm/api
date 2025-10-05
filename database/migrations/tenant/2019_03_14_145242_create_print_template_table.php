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
        Schema::create('print_template', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('模板名称');
            $table->string('type')->comment('模板类型');
            $table->unsignedTinyInteger('default')->default(0)->comment('是否默认');
            $table->unsignedTinyInteger('system')->default(0)->comment('系统模板');
            $table->text('content')->nullable()->comment('模板内容');
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
        Schema::dropIfExists('print_template');
    }
};
