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
        Schema::create('cashier_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->string('type')->comment('开票类型');
            $table->string('key')->comment('开票单号');
            $table->date('date')->comment('开票日期');
            $table->string('code')->nullable()->comment('发票代码');
            $table->string('number')->nullable()->comment('发票号码');
            $table->string('tax_number')->nullable()->comment('票据税号');
            $table->string('title')->nullable()->comment('开票抬头');
            $table->string('bank_name')->nullable()->comment('开户银行');
            $table->string('bank_account')->nullable()->comment('开户账号');
            $table->integer('create_user_id')->comment('开票人员id');
            $table->text('remark')->nullable()->comment('开票备注');
            $table->decimal('amount', 14, 4)->comment('开票总金额');
            $table->timestamps();
            $table->comment('开票信息主表');
        });

        Schema::create('cashier_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cashier_invoice_id')->index()->comment('开票单号');
            $table->uuid('customer_id')->index()->comment('顾客id');
            $table->uuid('cashier_id')->index()->comment('收费单号');
            $table->uuid('customer_product_id')->nullable()->comment('关联顾客成交项目明细表id');
            $table->uuid('customer_goods_id')->nullable()->comment('关联顾客成交物品明细表id');
            $table->integer('package_id')->nullable()->comment('套餐id');
            $table->string('package_name')->nullable()->comment('套餐名称');
            $table->integer('product_id')->nullable()->comment('产品id');
            $table->string('product_name')->nullable()->comment('产品名称');
            $table->integer('goods_id')->nullable()->comment('物品id');
            $table->string('goods_name')->nullable()->comment('物品名称');
            $table->string('name')->comment('开票名称');
            $table->unsignedInteger('times')->comment('数量');
            $table->unsignedInteger('unit_id')->nullable();
            $table->string('unit_name', 10)->nullable()->comment('单位');
            $table->string('specs')->nullable()->comment('物品规格');
            $table->decimal('invoice_amount', 14, 4)->comment('开票金额');
            $table->decimal('income', 14, 4)->comment('实收金额');
            $table->decimal('deposit', 14, 4)->comment('余额支付');
            $table->timestamps();
            $table->comment('开票明细表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_invoices');
        Schema::dropIfExists('cashier_invoice_details');
    }
};
