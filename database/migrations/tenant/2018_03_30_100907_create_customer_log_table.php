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
        Schema::create('customer_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->index();
            $table->string('action', 100)->nullable()->index()->comment('业务类型');
            $table->integer('user_id')->comment('操作人员');

            // 多态关系
            $table->uuid('logable_id');
            $table->string('logable_type');
            $table->index(['logable_id', 'logable_type']);

            $table->text('original')->nullable()->comment('变动字段更新前数据');
            $table->text('dirty')->nullable()->comment('变动字段更新后数据');
            $table->longText('remark')->nullable()->comment('操作日志');

            $table->timestamp('created_at', 0)->nullable()->index();
            $table->timestamp('updated_at', 0)->nullable();
            $table->comment('顾客操作日志');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_log');
    }
};
