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
        Schema::create('customer_talk', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('customer_id')->index();
            $table->string('name');

            // 多态
            $table->uuid('talk_id');
            $table->string('talk_type');
            $table->index(['talk_id', 'talk_type']);

            $table->timestamps();
            $table->comment('沟通记录表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_talk');
    }
};
