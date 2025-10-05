<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 经销商表(渠道代理列表)
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('distributor', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->integer('user_id')->comment('用户id');
            $table->integer('parentid')->default(0)->comment('上级id,没有则0');
            $table->tinyInteger('child')->default(0)->comment('是否有下级');
            $table->integer('number')->default(0)->comment('下线数量');
            $table->string('tree')->nullable()->comment('关系树');
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
        Schema::dropIfExists('distributor');
    }
};
