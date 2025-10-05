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
        Schema::create('customer_photos', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('customer_id')->comment('顾客id');
            $table->string('flag')->comment('相册类型(术前、术后、恢复期等）');
            $table->string('title')->comment('相册名称');
            $table->text('remark')->nullable()->comment('相册备注');
            $table->unsignedInteger('create_user_id')->comment('创建人');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_photos');
    }
};
