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
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->comment('所属门店');
            $table->string('name')->comment('工作组名称');
            $table->string('description')->comment('工作组描述');
            $table->unsignedBigInteger('create_user_id')->comment('创建人员');
            $table->timestamps();
            $table->comment('工作组表');
        });
        Schema::create('user_group_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_group_id')->comment('工作组ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->timestamps();
            $table->comment('工作组详情表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('user_group_details');
    }
};
