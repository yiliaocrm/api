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
        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('store_id')->comment('所属门店');
            $table->string('name')->comment('名称');
            $table->tinyInteger('status')->comment('状态：0停用、1空闲、2使用中、3预约');
            $table->integer('department_id')->unsigned()->comment('所属科室');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->comment('诊室表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('room');
    }
};
