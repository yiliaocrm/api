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
        Schema::create('department_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->comment('所属门店');
            $table->string('name')->comment('部门组名称');
            $table->string('description')->nullable()->comment('部门组描述');
            $table->unsignedBigInteger('create_user_id')->comment('创建人员');
            $table->timestamps();
            $table->comment('部门组表');
        });
        Schema::create('department_group_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_group_id')->comment('部门组ID');
            $table->unsignedBigInteger('department_id')->comment('部门ID');
            $table->timestamps();
            $table->comment('部门组详情表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_groups');
        Schema::dropIfExists('department_group_details');
    }
};
