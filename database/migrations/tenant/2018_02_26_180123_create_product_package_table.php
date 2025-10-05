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
        Schema::create('product_package', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->comment('标题');
            $table->integer('type_id')->comment('类别');
            $table->tinyInteger('splitable')->unsigned()->default(1)->comment('允许拆单');
            $table->tinyInteger('editable')->unsigned()->default(1)->comment('允许改价');
            $table->string('keyword')->nullable()->comment('搜索关键词');
            $table->integer('user_id')->comment('创建人');
            $table->decimal('amount', 14, 4)->comment('套餐总价');
            $table->tinyInteger('disabled')->default(0)->comment('是否禁用');
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
        Schema::dropIfExists('product_package');
    }
};
