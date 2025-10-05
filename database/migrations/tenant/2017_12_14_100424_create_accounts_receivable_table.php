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
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->integer('type_id')->comment('项目id,药品(物品)id,例如product_id');
            $table->string('type')->comment('业务类型');
            $table->decimal('payable', 14, 4)->comment('应收金额');
            $table->decimal('income', 14, 4)->comment('收款金额');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('欠款金额');
            $table->decimal('accumulated_repayment', 14, 4)->default(0)->comment('累计还款');
            $table->integer('user_id')->comment('收银员(操作员)');
            $table->timestamps();
            $table->comment('应收账款(客户欠款)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
    }
};
