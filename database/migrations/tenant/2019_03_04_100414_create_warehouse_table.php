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
        Schema::create('warehouse', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('keyword');
            $table->tinyInteger('disabled')->default(0);
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->comment('仓库');
        });
        Schema::create('warehouse_alarm', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('warehouse_id')->index()->comment('仓库id');
            $table->integer('goods_id')->index()->comment('商品id');
            $table->integer('min')->default(0)->comment('库存下限');
            $table->integer('max')->default(0)->comment('库存上限');
            $table->timestamps();
            $table->comment('仓库预警');
        });
        Schema::create('warehouse_users', function (Blueprint $table) {
            $table->id();
            $table->integer('warehouse_id')->index()->comment('仓库id');
            $table->integer('user_id')->index()->comment('用户id');
            $table->timestamps();
            $table->comment('仓库负责人');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse');
        Schema::dropIfExists('warehouse_alarm');
        Schema::dropIfExists('warehouse_users');
    }
};
