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
        Schema::create('consumable', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('单号');
            $table->date('date')->comment('单据日期');
            $table->uuid('customer_id')->index()->comment('顾客信息');
            $table->integer('warehouse_id')->comment('出料仓库');
            $table->integer('department_id')->comment('领料科室');
            $table->decimal('amount', 14, 4)->comment('领料成本');
            $table->uuid('customer_product_id')->nullable()->comment('关联顾客消费项目表ID');
            $table->integer('product_id')->nullable()->comment('项目id');
            $table->string('product_name')->default('')->comment('项目名称');
            $table->integer('user_id')->comment('领料人员');
            $table->integer('create_user_id')->comment('录单人员');
            $table->text('remark')->nullable()->comment('领料备注');
            $table->timestamps();
            $table->comment('用料登记表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable');
    }
};
