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
        Schema::create('rfm_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('规则名称');
            $table->string('description')->comment('规则描述');
            $table->string('r_operator')->comment('R值运算符');
            $table->unsignedInteger('r_value')->comment('R值');
            $table->string('f_operator')->comment('F值运算符');
            $table->unsignedInteger('f_value')->comment('F值');
            $table->string('m_operator')->comment('M值运算符');
            $table->decimal('m_value', 14, 2)->comment('M值');
            $table->unsignedInteger('count')->default(0)->comment('人数');
            $table->decimal('percent', 5, 2)->default(0)->comment('人数占比');
            $table->decimal('transaction_percent', 5, 2)->default(0)->comment('交易占比');
            $table->decimal('per_transaction', 14, 2)->default(0)->comment('人均交易金额');
            $table->timestamp('last_calculate_at')->nullable()->comment('最近一次计算时间');
            $table->timestamps();
            $table->comment('RFM规则表');
        });
        Schema::create('customer_rfms', function (Blueprint $table) {
            $table->uuid('customer_id')->index()->comment('客户ID');
            $table->unsignedInteger('recency')->comment('最近一次消费的时间间隔');
            $table->unsignedInteger('frequency')->comment('消费频率');
            $table->decimal('monetary', 14, 2)->comment('消费金额');
            $table->unsignedInteger('rfm_score')->comment('RFM综合评分');
            $table->unsignedBigInteger('rfm_rule_id')->index()->comment('RFM规则ID');
            $table->timestamps();
            $table->engine = 'MyISAM';
            $table->comment('客户RFM表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('rfm_rules');
        Schema::dropIfExists('customer_rfms');
    }
};
