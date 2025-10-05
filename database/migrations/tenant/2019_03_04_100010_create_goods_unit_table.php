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
        Schema::create('goods_unit', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('goods_id')->index();
            $table->integer('unit_id')->index();
            $table->smallInteger('rate')->unsigned()->comment('换算比例(单位关系)');
            $table->decimal('prebuyprice', 14, 4)->default(0)->comment('预设进价');
            $table->decimal('retailprice', 14, 4)->default(0)->comment('预设售价');
            $table->string('barcode')->nullable()->comment('条形码');
            $table->tinyInteger('basic')->comment('是否基本单位');
            $table->timestamps();
            $table->comment('商品单位关系表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_unit');
    }
};
