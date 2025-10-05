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
        Schema::create('manufacturer', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('名称');
            $table->string('short_name')->comment('简称');
            $table->string('keyword')->comment('索引关键词');
            $table->text('remark')->nullable()->comment('备注');
            $table->tinyInteger('disabled')->default(0)->comment('是否停用');
            $table->timestamps();
            $table->comment('生产厂家');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturer');
    }
};
