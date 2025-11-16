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
        Schema::create('reception_items', function (Blueprint $table) {
            $table->uuid('reception_id')->index();
            $table->integer('item_id');
            $table->tinyInteger('successful')->unsigned()->default(0)->comment('是否成交');
            $table->comment('分诊接待项目表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('reception_items');
    }
};
