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
        Schema::create('product_package_details', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->comment('类型:{项目}或{物品}');
            $table->integer('package_id')->unsigned()->comment('套餐ID');
            $table->integer('product_id')->unsigned()->nullable()->comment('项目ID');
            $table->string('product_name')->nullable()->comment('产品名称');
            $table->integer('goods_id')->unsigned()->nullable()->comment('物品ID');
            $table->string('goods_name')->nullable()->comment('物品名称');
            $table->integer('times')->unsigned()->comment('使用次数(数量)');
            $table->integer('unit_id')->nullable()->comment('单位(仅限物品)');
            $table->string('specs')->nullable()->comment('规格');
            $table->decimal('price', 14, 4)->comment('原价');
            $table->decimal('sales_price', 14, 4)->comment('执行价格');
            $table->integer('department_id')->unsigned()->nullable()->comment('结算科室');
            $table->text('remark')->nullable()->comment('备注信息');
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
        Schema::dropIfExists('product_package_details');
    }
};
