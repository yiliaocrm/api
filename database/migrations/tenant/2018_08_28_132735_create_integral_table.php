<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 客户积分明细表
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('integral', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->index()->comment('客户id');
            $table->tinyInteger('type')->comment('积分类型(1:充值赠送积分,2:项目消费积分,3:物品消费积分,4:积分换券,5:手工赠送,6:手工扣减,7:积分清零)');
            $table->char('type_id', 36)->nullable()->comment('业务单号(一般是cashier_id、也有可能是其他的，比如手工赠送)');
            $table->decimal('before', 14, 4)->default(0)->comment('变动前积分');
            $table->decimal('integral', 14, 4)->default(0)->comment('变动积分');
            $table->decimal('after', 14, 4)->default(0)->comment('变动后积分');
            $table->tinyInteger('expired')->default(0)->comment('积分是否过期(0:未过期,1:已过期)');
            $table->text('remark')->nullable()->comment('备注');
            $table->text('data')->nullable()->comment('冗余业务表数据json');
            $table->timestamp('created_at')->nullable()->index()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->comment('顾客积分明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('integral');
    }
};
