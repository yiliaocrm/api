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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('工作流名称');
            $table->text('description')->nullable()->comment('工作流描述');
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->unsignedBigInteger('create_user_id')->comment('创建人员ID');
            $table->boolean('all_customer')->default(false)->comment('是否适用于全部客户');
            $table->enum('type', ['trigger', 'periodic'])->default('trigger')->comment('工作流类型:trigger触发型、periodic周期型');
            $table->enum('status', ['draft', 'pending', 'active', 'paused', 'completed'])->default('draft')->comment('工作流状态:draft草稿、pending未开始、active进行中、paused已暂停、completed已结束');
            $table->timestamp('start_at')->nullable()->comment('工作流开始时间');
            $table->timestamp('end_at')->nullable()->comment('工作流结束时间');
            $table->string('cron')->nullable()->comment('执行时间点,cron表达式,仅周期型工作流有效');
            $table->timestamp('last_run_at')->nullable()->comment('上次执行时间');
            $table->timestamp('next_run_at')->nullable()->comment('下次执行时间');
            $table->string('version')->comment('版本号');
            $table->json('config')->nullable()->comment('前端编排配置');
            $table->json('rule_chain')->nullable()->comment('引擎规则链配置');
            $table->timestamps();
            $table->comment('工作流主表');
        });
        Schema::create('workflow_customer_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->comment('所属工作流ID');
            $table->unsignedBigInteger('customer_group_id')->comment('目标人群ID');
            $table->timestamps();
            $table->comment('工作流目标人群表');
        });
        Schema::create('workflow_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->comment('工作流分类表');
        });
        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('模板名称');
            $table->unsignedBigInteger('category_id')->comment('模板分类ID');
            $table->string('target')->nullable()->comment('目标客户');
            $table->string('staff')->nullable()->comment('推荐执行员工');
            $table->string('remark')->nullable()->comment('模板说明');
            $table->json('config')->nullable()->comment('模板配置');
            $table->timestamps();
            $table->comment('工作流模板表');
        });
        Schema::create('workflow_template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->timestamps();
            $table->comment('工作流模板分类表');
        });
        Schema::create('workflow_node_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('类型名称');
            $table->string('description')->nullable()->comment('类型描述');
            $table->string('icon')->nullable()->comment('类型图标');
            $table->json('dsl')->nullable()->comment('编排引擎DSL配置');
            $table->json('template')->nullable()->comment('模板配置');
            $table->timestamps();
            $table->comment('工作流节点类型表');
        });
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->comment('所属工作流ID');
            $table->string('name')->comment('节点名称');
            $table->string('type')->comment('节点类型:取workflow_node_types冗余');
            $table->unsignedBigInteger('type_id')->comment('节点类型ID');
            $table->json('config')->nullable()->comment('前端节点配置');
            $table->timestamps();
            $table->comment('工作流节点配置表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('workflow_customer_groups');
        Schema::dropIfExists('workflow_categories');
        Schema::dropIfExists('workflow_templates');
        Schema::dropIfExists('workflow_template_categories');
        Schema::dropIfExists('workflow_node_types');
        Schema::dropIfExists('workflow_nodes');
    }
};
