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
        Schema::create('outpatient_prescription_detail', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('outpatient_prescription_id')->comment('处方签id');
            $table->uuid('reception_id')->index()->comment('分诊ID');
            $table->uuid('customer_goods_id')->nullable()->comment('使用存药(已购买的药品)');
            $table->integer('goods_id')->comment('药品id');
            $table->string('goods_name')->comment('药品名称');
            $table->integer('package_id')->nullable()->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->string('specs')->nullable()->comment('药品规格');
            $table->integer('number')->comment('数量(医生开的药品数量)');
            $table->integer('goods_unit')->comment('商品单位');
            $table->decimal('price', 14, 4)->comment('单价');
            $table->decimal('amount', 14, 4)->comment('成交价');
            $table->string('group', 30)->nullable()->comment('组别');
            $table->string('dosage', 30)->nullable()->comment('每次用量');
            $table->string('dosage_unit', 30)->nullable()->comment('用量单位');
            $table->string('frequency', 30)->nullable()->comment('用药频次');
            $table->tinyInteger('days')->nullable()->comment('天数');
            $table->string('ways', 30)->nullable()->comment('用法');
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
        Schema::dropIfExists('outpatient_prescription_detail');
    }
};
