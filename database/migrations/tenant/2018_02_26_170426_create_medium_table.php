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
        Schema::create('medium', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('渠道名称');
            $table->smallInteger('parentid')->comment('父级ID');
            $table->tinyInteger('child')->default(0);

            $table->string('contact')->nullable()->comment('联系人');
            $table->string('phone')->nullable()->comment('联系电话');
            $table->string('address')->nullable()->comment('联系地址');
            $table->string('bank')->nullable()->comment('开户银行');
            $table->string('bank_account')->nullable()->comment('银行卡号');
            $table->string('bank_name')->nullable()->comment('账户名称');
            $table->tinyInteger('rate')->default(0)->comment('返佣比例');
            $table->string('user_id')->nullable()->comment('渠道负责人');

            $table->string('tree')->nullable();
            $table->integer('order')->default(0)->comment('排序');
            $table->string('keyword')->nullable();
            $table->text('remark')->nullable()->comment('备注');
            $table->unsignedInteger('create_user_id')->default(1)->comment('创建人');
            $table->timestamps();
            $table->comment('信息来源表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('medium');
    }
};
