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
        Schema::create('customer_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->comment('顾客ID');
            $table->integer('item_id')->comment('项目ID');
            $table->uuidMorphs('itemable', 'customer_itemable_index');
            $table->timestamps();
            $table->index(['customer_id', 'item_id'], 'customer_item_index');
            $table->comment('顾客咨询项目表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_items');
    }
};

