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
        Schema::create('followup', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->tinyInteger('type')->nullable()->comment('回访类型');
            $table->tinyInteger('status')->default(1)->comment('回访状态(1:未回访、2:已回访)');
            $table->tinyInteger('tool')->nullable()->comment('回访工具');
            $table->string('title')->comment('回访标题');
            $table->date('date')->index()->comment('计划回访日期');
            $table->dateTime('time')->index()->nullable()->comment('实际回访时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->integer('followup_user')->index()->comment('提醒人员');
            $table->integer('execute_user')->index()->nullable()->comment('执行人员');
            $table->integer('user_id')->index()->comment('录单人员');
            $table->string('callid')->nullable()->comment('呼叫id');
            $table->uuid('cc_cdr_id')->nullable()->comment('关联通话记录id');
            $table->timestamps();
            $table->comment('回访记录表');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('followup');
    }
};
