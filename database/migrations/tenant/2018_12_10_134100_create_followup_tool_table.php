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
        Schema::create('followup_tool', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('remark')->nullable()->default(null);
            $table->timestamps();
            $table->comment('回访工具表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_tool');
    }
};
