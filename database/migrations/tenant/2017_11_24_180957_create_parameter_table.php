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
        Schema::create('parameters', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'number', 'boolean', 'array', 'object'])
                ->default('string')
                ->comment('参数类型：string,number,boolean,array,object');
            $table->string('remark')->comment('备注');
            $table->timestamps();
            $table->comment('机构端参数配置表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('parameter');
    }
};
