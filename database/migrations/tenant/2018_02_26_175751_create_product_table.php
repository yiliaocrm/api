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
        Schema::create('product', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('项目名称');
            $table->string('print_name')->nullable()->comment('打印名称');
            $table->string('keyword')->comment('检索字段');
            $table->integer('type_id')->comment('类别');
            $table->decimal('price', 14, 4)->comment('项目原价');
            $table->decimal('sales_price', 14, 4)->comment('执行价格');
            $table->integer('times')->comment('使用次数');
            $table->integer('expiration')->comment('使用期限');
            $table->integer('expense_category_id')->default(1)->comment('费用类别');
            $table->string('specs')->nullable()->comment('项目规格');
            $table->integer('department_id')->nullable()->comment('结算科室(业绩归属科室)');
            $table->integer('deduct_department')->comment('划扣科室');
            $table->tinyInteger('deduct')->default(1)->comment('需要划扣');
            $table->tinyInteger('commission')->default(1)->comment('(现场咨询)开单提成');
            $table->tinyInteger('integral')->default(1)->comment('(顾客)消费积分');
            $table->tinyInteger('successful')->unsigned()->default(1)->comment('统计成交(例如,有些拓客项目:小气泡,缴费后不统计成交)');
            $table->text('remark')->nullable()->comment('备注');
            $table->tinyInteger('disabled')->default(0)->comment('禁用');
            $table->comment('项目表');
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
        Schema::dropIfExists('product');
    }
};
