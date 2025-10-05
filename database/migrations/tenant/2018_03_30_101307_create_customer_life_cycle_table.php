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
        Schema::create('customer_life_cycle', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->string('name');

            // 多态表
            $table->uuid('cycle_id');
            $table->string('cycle_type');
            $table->index(['cycle_id', 'cycle_type']);

            $table->uuid('customer_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_life_cycle');
    }
};
