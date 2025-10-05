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
        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->string('idcard')->nullable();
            $table->string('file_number')->nullable()->comment('档案号');
            $table->string('name');
            $table->string('qq', 20)->nullable()->comment('QQ号码');
            $table->string('wechat', 30)->nullable()->comment('微信号码');
            $table->string('sfz', 30)->nullable()->comment('身份证');
            $table->integer('job_id')->nullable()->comment('职业信息');
            $table->integer('economic_id')->nullable()->comment('经济能力');
            $table->integer('marital')->nullable()->comment('婚姻状况(1:未知,2:未婚,3:已婚)');
            $table->tinyInteger('sex');
            $table->date('birthday')->nullable();
            $table->integer('age')->nullable();
            $table->integer('address_id');
            $table->integer('level_id')->default(1)->comment('会员等级');
            $table->integer('medium_id')->index()->nullable()->comment('首次来源');
            $table->unsignedInteger('referrer_user_id')->nullable()->comment('推荐员工id');
            $table->uuid('referrer_customer_id')->nullable()->comment('推荐客户id');
            $table->integer('department_id')->default(0)->comment('归属科室');
            $table->decimal('total_payment', 14, 4)->default(0)->comment('累计付款(交钱就算)');
            $table->decimal('balance', 14, 4)->default(0)->comment('账号余额');
            $table->decimal('amount', 14, 4)->default(0)->comment('累计消费(预收款不算)');
            $table->decimal('arrearage', 14, 4)->default(0)->comment('累计欠款');
            $table->decimal('integral', 14, 4)->default(0)->comment('现有积分');
            $table->decimal('expend_integral', 14, 4)->default(0)->comment('累计使用积分');
            $table->timestamp('first_time')->nullable()->comment('初诊日期(首次上门)');
            $table->timestamp('last_time')->nullable()->comment('最近上门');
            $table->timestamp('last_followup')->nullable()->comment('最后一次回访时间');
            $table->timestamp('last_treatment')->nullable()->comment('最后一次治疗时间');
            $table->integer('ascription')->nullable()->comment('开发人员');
            $table->integer('consultant')->nullable()->comment('现场咨询');
            $table->integer('service_id')->nullable()->comment('专属客服');
            $table->integer('doctor_id')->nullable()->comment('主治医生');
            $table->integer('user_id')->comment('创建人员');
            $table->string('keyword');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index(['consultant', 'ascription']);
            $table->comment('顾客信息表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};
