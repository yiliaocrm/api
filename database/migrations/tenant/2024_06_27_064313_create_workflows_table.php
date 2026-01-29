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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();

            // 基础信息
            $table->string('name')->comment('工作流名称');
            $table->text('description')->nullable()->comment('工作流描述');
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->unsignedBigInteger('create_user_id')->comment('创建人员ID');

            // n8n 对齐字段
            $table->string('n8n_id')->nullable()->unique()->comment('n8n工作流ID');
            $table->boolean('active')->default(false)->comment('是否激活（对应n8n的active字段）');
            $table->json('nodes')->nullable()->comment('节点配置（对应n8n的nodes数组）');
            $table->json('connections')->nullable()->comment('节点连接配置（对应n8n的connections对象）');
            $table->json('settings')->nullable()->comment('工作流设置（对应n8n的settings对象）');
            $table->json('static_data')->nullable()->comment('静态数据存储（对应n8n的staticData）');
            $table->json('tags')->nullable()->comment('标签数组（对应n8n的tags）');

            // 业务字段
            $table->boolean('all_customer')->default(false)->comment('是否适用于全部客户');
            $table->enum('type', ['trigger', 'periodic'])->default('trigger')->comment('工作流类型:trigger触发型、periodic周期型');
            $table->enum('status', ['draft', 'pending', 'active', 'paused', 'completed'])->default('draft')->comment('工作流状态:draft草稿、pending未开始、active进行中、paused已暂停、completed已结束');

            // 执行时间
            $table->timestamp('start_at')->nullable()->comment('工作流开始时间');
            $table->timestamp('end_at')->nullable()->comment('工作流结束时间');
            $table->string('cron')->nullable()->comment('执行时间点,cron表达式,仅周期型工作流有效');
            $table->timestamp('last_run_at')->nullable()->comment('上次执行时间');
            $table->timestamp('next_run_at')->nullable()->comment('下次执行时间');

            // 版本控制
            $table->string('version')->default('1.0.0')->comment('版本号');

            // 遗留字段（保留兼容性）
            $table->json('config')->nullable()->comment('前端编排配置（遗留字段）');
            $table->json('rule_chain')->nullable()->comment('引擎规则链配置（遗留字段）');

            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index('category_id');
            $table->index('create_user_id');
            $table->index('active');
            $table->index('status');
            $table->index('type');

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
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('节点类型标识');
            $table->string('name')->unique()->comment('节点类型名称');
            $table->string('icon')->nullable()->comment('节点类型图标');
            $table->string('color')->nullable()->comment('节点图标颜色');
            $table->string('description')->nullable()->comment('类型描述');
            $table->json('dsl')->nullable()->comment('编排引擎DSL配置');
            $table->json('template')->nullable()->comment('模板配置');
            $table->timestamps();
            $table->comment('工作流节点类型表');
        });
        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->comment('所属工作流ID');
            $table->string('n8n_execution_id')->nullable()->comment('n8n执行ID');

            // 执行信息
            $table->enum('status', ['running', 'success', 'error', 'waiting', 'canceled'])->default('running')->comment('执行状态');
            $table->timestamp('started_at')->nullable()->comment('开始时间');
            $table->timestamp('finished_at')->nullable()->comment('结束时间');
            $table->integer('duration')->nullable()->comment('执行时长（毫秒）');

            // 执行数据
            $table->json('input_data')->nullable()->comment('输入数据');
            $table->json('output_data')->nullable()->comment('输出数据');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->json('execution_data')->nullable()->comment('完整执行数据');

            // 触发信息
            $table->string('trigger_type')->nullable()->comment('触发类型：manual/webhook/schedule');
            $table->unsignedBigInteger('trigger_user_id')->nullable()->comment('触发用户ID');

            $table->timestamps();

            // 索引
            $table->index('workflow_id');
            $table->index('status');
            $table->index('started_at');

            $table->comment('工作流执行记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('workflow_nodes');
        Schema::dropIfExists('workflow_template_categories');
        Schema::dropIfExists('workflow_templates');
        Schema::dropIfExists('workflow_categories');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_customer_groups');
    }
};
