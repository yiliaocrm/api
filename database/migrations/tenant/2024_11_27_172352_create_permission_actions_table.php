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
        Schema::create('permission_actions', function (Blueprint $table) {
            $table->id();
            $table->string('permission')->comment('权限名称');
            $table->string('controller')->comment('控制器');
            $table->string('action')->default('*')->comment('方法,多个方法逗号隔开');
            $table->string('except')->nullable()->comment('排除某个方法,多个方法逗号隔开');
            $table->timestamps();
            $table->comment('权限动作');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_actions');
    }
};
