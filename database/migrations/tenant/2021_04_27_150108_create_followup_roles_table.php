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
        Schema::create('followup_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('回访角色名称');
            $table->string('value')->comment('回访角色值');
            $table->timestamps();
            $table->comment('回访角色表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_roles');
    }
};
