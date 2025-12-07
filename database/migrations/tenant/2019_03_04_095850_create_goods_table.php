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
        Schema::create('goods', function (Blueprint $table) {
            $table->increments('id')->comment('');
            $table->string('name')->comment('商品名称');
            $table->string('short_name')->comment('商品简称');
            $table->text('keyword')->comment('检索字段');
            $table->string('barcode')->nullable()->comment('表形码（goods_unit表的冗余）');
            $table->integer('type_id')->comment('类别');
            $table->integer('expense_category_id')->default(1)->comment('费用类别');
            $table->unsignedTinyInteger('high_value')->default(0)->comment('高值耗材');
            $table->tinyInteger('is_drug')->default(0)->comment('商品类别(是否药品)');

            $table->string('approval_number')->nullable()->comment('批准文号');

            $table->string('specs')->nullable()->comment('规格型号');
            $table->integer('warn_days')->default(0)->comment('过期预警天数');
            $table->integer('min')->default(0)->comment('库存下限');
            $table->integer('max')->default(0)->comment('库存上限');
            $table->tinyInteger('commission')->default(0)->comment('开单提成');
            $table->tinyInteger('integral')->default(0)->comment('消费积分');

            $table->decimal('inventory_number', 14, 4)->default(0)->comment('库存数量');
            $table->decimal('inventory_amount', 14, 4)->default(0)->comment('库存成本');

            $table->text('remark')->nullable()->comment('备注');
            $table->text('data')->nullable()->comment('数据字段(json格式)用来存储一些信息');
            $table->tinyInteger('disabled')->default(0)->comment('停用');
            $table->timestamps();
            $table->comment('商品表、药品表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('goods');
    }
};
