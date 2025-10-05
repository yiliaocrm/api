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
        Schema::create('customer_wechats', function (Blueprint $table) {
            $table->id();
            $table->string('open_id');
            $table->uuid('customer_id');
            $table->string('phone')->comment('手机号');
            $table->string('nickname')->comment('用户昵称');
            $table->string('avatar')->comment('用户头像');
            $table->string('country')->comment('国家');
            $table->string('province')->comment('省份');
            $table->string('city')->comment('城市');
            $table->timestamps();
            $table->comment('顾客微信表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_wechats');
    }
};
