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
        Schema::create('department_picking_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('keyword')->nullable();
            $table->timestamps();
            $table->comment('科室领料单领料类别表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('department_picking_types');
    }
};
