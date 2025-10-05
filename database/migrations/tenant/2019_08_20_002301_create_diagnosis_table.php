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
        Schema::create('diagnosis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('category_id')->comment('诊断类别');
            $table->string('name')->comment('诊断名称');
            $table->string('code')->nullable()->comment('ICD诊断编码');
            $table->string('keyword')->index()->comment('关键词搜索');
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
        Schema::dropIfExists('diagnosis');
    }
};
