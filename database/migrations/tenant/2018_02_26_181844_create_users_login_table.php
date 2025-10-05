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
        Schema::create('users_login', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->tinyInteger('type')->comment('客户端:1、pc,2:app');
            $table->string('ip', 16);
            $table->string('country')->nullable()->comment('国家');
            $table->string('province')->nullable()->comment('省');
            $table->string('city')->nullable()->comment('市');
            $table->string('browser')->nullable()->comment('浏览器');
            $table->string('platform')->nullable()->comment('操作平台');
            $table->string('fingerprint')->nullable()->comment('指纹');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->comment('员工登录记录表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users_login');
    }
};
