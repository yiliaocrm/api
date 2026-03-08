<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_photos', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('customer_id')->comment('顾客id');
            $table->unsignedInteger('photo_type_id')->comment('照片类型ID');
            $table->string('title')->comment('相册名称');
            $table->text('remark')->nullable()->comment('相册备注');
            $table->unsignedInteger('create_user_id')->comment('创建人');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_photos');
    }
};
