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
        Schema::create('admin_parameters', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'number', 'boolean', 'array', 'object'])
                ->default('string')
                ->comment('参数类型：string,number,boolean,array,object');
            $table->string('remark')->comment('备注');
            $table->timestamps();
            $table->comment('后台参数配置表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_parameters');
    }
};
