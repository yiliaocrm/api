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
        Schema::create('cashier_pay', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cashier_id')->index()->comment('收费单id');
            $table->integer('accounts_id')->unsigned()->comment('付款账户');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->decimal('income', 14, 4)->comment('付款金额');
            $table->string('remark')->nullable()->comment('备注');
            $table->integer('user_id')->comment('收银员');
            $table->timestamps();
            $table->comment('收费时，付款账户');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_pay');
    }
};
