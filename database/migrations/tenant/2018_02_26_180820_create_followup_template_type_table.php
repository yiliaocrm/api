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
        Schema::create('followup_template_type', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->smallInteger('parentid');
            $table->tinyInteger('child')->default(0);
            $table->string('keyword')->nullable();
            $table->string('tree')->nullable();
            $table->comment('回访模板类型表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_template_type');
    }
};
