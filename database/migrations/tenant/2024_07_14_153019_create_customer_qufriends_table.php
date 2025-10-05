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
        Schema::create('customer_qufriends', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->index()->comment('顾客ID');
            $table->uuid('related_customer_id')->index()->comment('关联顾客ID');
            $table->unsignedInteger('qufriend_id')->index()->comment('亲友关系ID');
            $table->unsignedInteger('create_user_id')->comment('创建人ID');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->comment('顾客亲友关系表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_qufriends');
    }
};
