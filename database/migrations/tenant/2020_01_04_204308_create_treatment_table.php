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
        Schema::create('treatment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->uuid('customer_product_id')->index()->comment('顾客项目成交表id');
            $table->integer('product_id')->comment('划扣项目id');
            $table->string('product_name')->comment('项目名称');
            $table->integer('package_id')->nullable()->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->integer('department_id')->index()->comment('执行科室');
            $table->integer('times')->unsigned()->comment('划扣次数');
            $table->decimal('price', 14, 4)->default(0)->comment('划扣价格');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('本单欠款金额');
            $table->decimal('coupon', 14, 4)->default(0)->comment('券支付');
            $table->text('participants')->nullable()->comment('参与配台人员');
            $table->text('remark')->nullable()->comment('备注');
            $table->integer('user_id')->index()->comment('录单人员');
            $table->unsignedTinyInteger('status')->comment('状态:1:正常,2:撤销');
            $table->unsignedInteger('undo_user_id')->nullable()->comment('撤销记录操作人员');
            $table->timestamps();
            $table->comment('划扣记录表');
            $table->index(['created_at']);
        });
        Schema::create('treatment_participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('treatment_id')->index();
            $table->integer('role_id')->unsigned()->comment('配台角色');
            $table->integer('user_id')->unsigned()->comment('配台人员');
            $table->comment('划扣参与配台人员表');
            $table->index(['treatment_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment');
        Schema::dropIfExists('treatment_participants');
    }
};
