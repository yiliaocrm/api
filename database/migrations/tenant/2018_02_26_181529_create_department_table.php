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
        Schema::create('department', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('store_id')->unsigned()->default(1)->comment('门店ID');
            $table->string('name')->comment('部门名称');
            $table->tinyInteger('disabled')->default(0)->comment('停用');
            $table->tinyInteger('primary')->default(0)->comment('医疗部门');
            $table->text('remark')->nullable()->comment('备注');
            $table->string('keyword')->comment('搜索关键词');
            $table->timestamps();
            $table->comment('部门科室表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('department');
    }
};
