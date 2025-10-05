<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->comment('顾客ID');
            $table->uuidMorphs('orderable', 'orderable_id_orderable_type_index');
            $table->enum('status', ['draft', 'pending', 'paid', 'completed', 'cancelled'])
                ->default('draft')
                ->comment('订单状态: draft(草稿)、pending(待处理)、paid(已支付)、completed(已完成)、cancelled(已取消)');
            $table->timestamps();
            $table->comment('顾客订单主表');
        });
        Schema::create('customer_order_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->comment('顾客订单商品明细表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
        Schema::dropIfExists('customer_order_details');
    }
};
