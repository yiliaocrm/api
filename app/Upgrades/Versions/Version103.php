<?php

namespace App\Upgrades\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class Version103 extends BaseVersion
{
    /**
     * 版本号
     */
    public function version(): string
    {
        return '1.0.3';
    }

    /**
     * 中央数据库变更 - 创建 upgrade_logs 表（升级系统自举）
     */
    public function centralUp(): void
    {
        $this->info('创建 upgrade_logs 表');
        $this->createTableIfNotExists('upgrade_logs', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->comment('版本号');
            $table->enum('phase', ['central', 'tenant', 'global'])->comment('执行阶段');
            $table->string('tenant_id', 50)->nullable()->comment('租户ID，仅 tenant 阶段');
            $table->string('tenant_name')->nullable()->comment('租户名称');
            $table->enum('status', ['running', 'success', 'error'])->default('running');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable()->comment('耗时(毫秒)');
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();
            $table->timestamps();
            $table->index(['version', 'phase', 'tenant_id']);
            $table->index(['status', 'version']);
        });
    }

    /**
     * 租户数据库变更
     */
    public function tenantUp(): void
    {
        $this->tenantInfo('开始执行 1.0.3 版本升级');

        // 创建表（幂等）
        $this->tenantInfo('创建表 customer_photo_types');
        $this->createTableIfNotExists('customer_photo_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('类型名称');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        $this->tenantInfo('创建表 workflows');
        $this->createTableIfNotExists('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('工作流名称');
            $table->text('description')->nullable()->comment('工作流描述');
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->unsignedBigInteger('create_user_id')->comment('创建人ID');
            $table->boolean('all_customer')->default(false)->comment('是否适用于全部客户');
            $table->enum('type', ['trigger', 'periodic'])->default('trigger')->comment('工作流类型');
            $table->enum('status', ['active', 'paused'])->default('paused')->comment('工作流状态: active=已发布, paused=未发布');
            $table->json('cron')->nullable()->comment('周期调度配置，发布时由后端生成');
            $table->timestamp('last_run_at')->nullable()->comment('上次执行时间');
            $table->timestamp('next_run_at')->nullable()->comment('下次执行时间');
            $table->unsignedInteger('dispatch_chunk_size')->default(2000)->comment('分片大小');
            $table->unsignedInteger('dispatch_concurrency')->default(12)->comment('并发数');
            $table->unsignedInteger('execution_batch_size')->default(200)->comment('执行批次大小');
            $table->unsignedInteger('max_queue_lag')->default(1000)->comment('队列积压阈值');
            $table->json('rule_chain')->nullable()->comment('流程图规则链配置');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('工作流主表');
        });

        $this->tenantInfo('创建表 workflow_customer_groups');
        $this->createTableIfNotExists('workflow_customer_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->comment('所属工作流ID');
            $table->unsignedBigInteger('customer_group_id')->comment('客户分组ID');
            $table->timestamps();
            $table->comment('工作流目标客户分组表');
        });

        $this->tenantInfo('创建表 workflow_categories');
        $this->createTableIfNotExists('workflow_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->comment('工作流分类表');
        });

        $this->tenantInfo('创建表 workflow_templates');
        $this->createTableIfNotExists('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('模板名称');
            $table->unsignedBigInteger('category_id')->comment('模板分类ID');
            $table->string('target')->nullable()->comment('目标客户');
            $table->string('staff')->nullable()->comment('推荐执行人员');
            $table->string('remark')->nullable()->comment('模板说明');
            $table->json('config')->nullable()->comment('模板配置');
            $table->timestamps();
            $table->comment('工作流模板表');
        });

        $this->tenantInfo('创建表 workflow_template_categories');
        $this->createTableIfNotExists('workflow_template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('模板分类名称');
            $table->timestamps();
            $table->comment('工作流模板分类表');
        });

        $this->tenantInfo('创建表 workflow_components');
        $this->createTableIfNotExists('workflow_components', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('组件标识');
            $table->string('name')->unique()->comment('组件名称');
            $table->string('icon')->nullable()->comment('组件图标');
            $table->string('bg_color')->nullable()->comment('组件背景色');
            $table->string('description')->nullable()->comment('组件描述');
            $table->json('template')->nullable()->comment('组件默认模板配置');
            $table->json('output_schema')->nullable()->comment('组件输出变量定义');
            $table->unsignedBigInteger('type_id')->nullable()->comment('组件类型ID');
            $table->timestamps();
            $table->comment('工作流组件表');
        });

        $this->tenantInfo('创建表 workflow_component_types');
        $this->createTableIfNotExists('workflow_component_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('组件类型名称');
            $table->string('key')->unique()->comment('组件类型标识');
            $table->string('icon')->nullable()->comment('组件类型图标');
            $table->string('bg_color')->nullable()->comment('组件类型背景色');
            $table->string('description')->nullable()->comment('组件类型描述');
            $table->timestamps();
            $table->comment('工作流组件类型表');
        });

        $this->tenantInfo('创建表 workflow_events');
        $this->createTableIfNotExists('workflow_events', function (Blueprint $table) {
            $table->id();
            $table->string('event')->comment('事件标识');
            $table->string('event_name')->comment('事件展示名称');
            $table->string('category_name')->comment('事件分类名称');
            $table->timestamps();
            $table->comment('工作流触发事件表');
        });

        $this->tenantInfo('创建表 workflow_versions');
        $this->createTableIfNotExists('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->comment('所属工作流ID');
            $table->unsignedInteger('version_no')->comment('版本号（工作流内递增）');
            $table->enum('source', ['save', 'publish', 'restore'])->default('save')->comment('版本来源');
            $table->unsignedBigInteger('create_user_id')->nullable()->comment('创建人ID');
            $table->json('snapshot')->comment('工作流快照');
            $table->timestamps();
            $table->unique(['workflow_id', 'version_no'], 'wf_versions_wf_id_version_no_uniq');
            $table->index('workflow_id', 'wf_versions_wf_id_idx');
            $table->index('source', 'wf_versions_source_idx');
            $table->comment('工作流历史版本表');
        });

        $this->tenantInfo('创建表 workflow_runs');
        $this->createTableIfNotExists('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->comment('工作流ID');
            $table->unsignedBigInteger('workflow_version_id')->nullable()->comment('版本ID');
            $table->string('run_key')->comment('幂等键 (yyyyMMddHHmm)');
            $table->enum('status', ['pending', 'running', 'completed', 'canceled', 'error'])->default('pending')->comment('运行状态');
            $table->enum('target_mode', ['all', 'groups'])->default('all')->comment('目标模式');
            $table->json('group_ids_json')->nullable()->comment('分组ID列表');
            $table->string('cursor_last_customer_id', 36)->nullable()->comment('游标最后客户ID');
            $table->unsignedInteger('total_target')->default(0)->comment('目标总数');
            $table->timestamp('dispatch_completed_at')->nullable()->comment('分发完成时间');
            $table->unsignedInteger('enqueued_count')->default(0)->comment('已入队数');
            $table->unsignedInteger('processed_count')->default(0)->comment('已处理数');
            $table->unsignedInteger('success_count')->default(0)->comment('成功数');
            $table->unsignedInteger('error_count')->default(0)->comment('错误数');
            $table->timestamp('cancel_requested_at')->nullable()->comment('取消请求时间');
            $table->timestamp('started_at')->nullable()->comment('开始时间');
            $table->timestamp('finished_at')->nullable()->comment('结束时间');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->timestamps();
            $table->unique(['workflow_id', 'run_key'], 'wf_runs_wf_key_uniq');
            $table->index('dispatch_completed_at', 'wf_run_dispatch_completed_idx');
            $table->index(['status', 'created_at'], 'wf_runs_status_created_idx');
            $table->index('workflow_id', 'wf_runs_wf_id_idx');
            $table->comment('工作流运行记录表（批次层）- 管理批量执行任务的整体进度和统计');
        });

        $this->tenantInfo('创建表 workflow_executions');
        $this->createTableIfNotExists('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->comment('所属工作流ID');
            $table->unsignedBigInteger('workflow_version_id')->nullable()->comment('执行时的工作流版本ID');
            $table->unsignedBigInteger('run_id')->nullable()->comment('关联的运行记录ID');
            $table->enum('status', ['running', 'success', 'error', 'waiting', 'canceled'])->default('running')->comment('执行状态');
            $table->timestamp('started_at')->nullable()->comment('开始时间');
            $table->timestamp('finished_at')->nullable()->comment('结束时间');
            $table->integer('duration')->nullable()->comment('执行时长(毫秒)');
            $table->json('input_data')->nullable()->comment('执行输入参数');
            $table->json('output_data')->nullable()->comment('执行输出结果');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->json('execution_data')->nullable()->comment('完整执行数据');
            $table->string('current_node_id')->nullable()->comment('当前执行节点ID');
            $table->string('next_node_id')->nullable()->comment('下一待执行节点ID');
            $table->json('context_data')->nullable()->comment('执行上下文数据');
            $table->timestamp('waiting_until')->nullable()->comment('等待恢复时间');
            $table->string('trigger_event')->nullable()->comment('触发事件');
            $table->string('trigger_model_type')->nullable()->comment('触发模型类型');
            $table->string('trigger_model_id')->nullable()->comment('触发模型ID');
            $table->unsignedInteger('lock_version')->default(0)->comment('乐观锁版本号');
            $table->string('trigger_type')->nullable()->comment('触发类型');
            $table->unsignedBigInteger('trigger_user_id')->nullable()->comment('触发用户ID');
            $table->timestamps();
            $table->index('workflow_id', 'wf_exec_wf_id_idx');
            $table->index('workflow_version_id', 'wf_exec_version_id_idx');
            $table->unique(['workflow_id', 'run_id', 'trigger_model_type', 'trigger_model_id'], 'wf_exec_run_trigger_uniq');
            $table->index(['run_id', 'status', 'id'], 'wf_exec_run_status_id_idx');
            $table->index(['status', 'waiting_until'], 'wf_exec_status_wait_idx');
            $table->index(['status', 'finished_at', 'id'], 'wf_exec_status_finished_id_idx');
            $table->index('trigger_event', 'wf_exec_trigger_event_idx');
            $table->comment('工作流执行记录表（执行层）- 管理单个目标的完整执行流程和上下文');
        });

        $this->tenantInfo('创建表 workflow_condition_fields');
        $this->createTableIfNotExists('workflow_condition_fields', function (Blueprint $table) {
            $table->id();
            $table->string('table')->comment('数据表名');
            $table->string('field')->comment('字段名');
            $table->string('field_type')->comment('字段类型');
            $table->string('table_name')->comment('表显示名');
            $table->string('field_name')->comment('字段显示名');
            $table->tinyInteger('auto_join')->default(0)->comment('是否自动连表');
            $table->text('query_config')->nullable()->comment('特殊查询配置');
            $table->string('keyword')->nullable()->comment('搜索关键词');
            $table->string('api')->nullable()->comment('远程数据接口');
            $table->string('component')->comment('前端组件类型');
            $table->text('component_params')->nullable()->comment('组件参数');
            $table->text('operators')->comment('支持的操作符');
            $table->string('context_binding')->nullable()->comment('上下文变量绑定路径');
            $table->comment('工作流业务判断条件字段配置表');
        });

        $this->tenantInfo('创建表 workflow_execution_steps');
        $this->createTableIfNotExists('workflow_execution_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_execution_id')->comment('执行记录ID');
            $table->unsignedBigInteger('workflow_version_id')->nullable()->comment('执行时的工作流版本ID');
            $table->string('node_id')->nullable()->comment('节点ID');
            $table->string('node_type')->nullable()->comment('节点类型');
            $table->string('node_name')->nullable()->comment('节点名称');
            $table->enum('status', ['running', 'success', 'error', 'skipped'])->default('running')->comment('步骤状态');
            $table->unsignedInteger('attempt')->default(1)->comment('重试次数');
            $table->json('input_data')->nullable()->comment('步骤输入参数');
            $table->json('output_data')->nullable()->comment('步骤输出结果');
            $table->text('error_message')->nullable()->comment('步骤错误信息');
            $table->timestamp('started_at')->nullable()->comment('步骤开始时间');
            $table->timestamp('finished_at')->nullable()->comment('步骤结束时间');
            $table->unsignedInteger('duration_ms')->nullable()->comment('步骤耗时(毫秒)');
            $table->timestamps();
            $table->index('workflow_execution_id', 'wf_step_exec_id_idx');
            $table->index('workflow_version_id', 'wf_step_version_id_idx');
            $table->index(['workflow_execution_id', 'node_id', 'attempt'], 'wf_step_exec_node_attempt_idx');
            $table->comment('工作流执行步骤表（步骤层）- 记录每个节点的执行细节和性能数据');
        });

        // 修改表（幂等）
        $this->tenantInfo('修改表 customer_photos');
        $this->addColumnIfNotExists('customer_photos', 'photo_type_id', function (Blueprint $table) {
            $table->unsignedInteger('photo_type_id')->comment('照片类型ID')->after('customer_id');
        });
        $this->dropColumnIfExists('customer_photos', 'flag');

        $this->tenantInfo('修改表 import_templates');
        $this->addColumnIfNotExists('import_templates', 'icon', function (Blueprint $table) {
            $table->string('icon')->nullable()->comment('模板图标')->after('id');
        });
        $this->dropColumnIfExists('import_templates', 'async_limit');

        $this->tenantInfo('修改表 import_tasks');
        $this->addColumnIfNotExists('import_tasks', 'pending_rows', function (Blueprint $table) {
            $table->integer('pending_rows')->comment('校验通过')->default(0)->after('total_rows');
            $table->integer('validated_fail_rows')->comment('校验失败')->default(0)->after('pending_rows');
            $table->integer('imported_rows')->comment('已导入')->default(0)->after('validated_fail_rows');
            $table->integer('imported_fail_rows')->comment('导入失败')->default(0)->after('imported_rows');
        });
        $this->dropColumnIfExists('import_tasks', 'success_rows');
        $this->dropColumnIfExists('import_tasks', 'fail_rows');

        $this->tenantInfo('修改表 import_task_details');
        $this->addColumnIfNotExists('import_task_details', 'validate_error_msg', function (Blueprint $table) {
            $table->text('validate_error_msg')->nullable()->comment('校验错误')->after('status');
            $table->text('import_error_msg')->nullable()->comment('导入错误')->after('validate_error_msg');
        });
        $this->dropColumnIfExists('import_task_details', 'error_msg');

        // 删除旧表
        $this->tenantInfo('删除旧 SOP 相关表');
        $this->dropTableIfExists('customer_sops');
        $this->dropTableIfExists('customer_sop_customer_groups');
        $this->dropTableIfExists('customer_sop_categories');
        $this->dropTableIfExists('customer_sop_templates');
        $this->dropTableIfExists('customer_sop_template_categories');
        $this->dropTableIfExists('customer_sop_node_types');
        $this->dropTableIfExists('customer_sop_nodes');

        // 初始化基础数据
        $this->tenantInfo('初始化基础数据');
        $this->runSeeder('Database\\Seeders\\Tenant\\CustomerPhotoTypeSeeder');
        $this->runSeeder('Database\\Seeders\\Tenant\\WorkflowEventSeeder');
        $this->runSeeder('Database\\Seeders\\Tenant\\WorkflowComponentTypeSeeder');
        $this->runSeeder('Database\\Seeders\\Tenant\\WorkflowComponentSeeder');
        $this->runSeeder('Database\\Seeders\\Tenant\\WorkflowConditionFieldsTableSeeder');
        $this->runSeeder('Database\\Seeders\\Tenant\\WorkflowCategorySeeder');
        $this->runSeeder('Database\\Seeders\\Tenant\\ImportTemplateSeeder');

        // 数据迁移
        $this->tenantInfo('更新客户照片数据');
        $this->updateCustomerPhotosData();

        $this->tenantInfo('1.0.3 版本升级完成');
    }

    /**
     * 全局操作 - 同步菜单和系统参数
     */
    public function globalUp(): void
    {
        $this->info('同步菜单数据');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\Admin\\MenusTableSeeder', '--force' => true]);

        $this->info('同步系统参数');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\Admin\\AdminParameterSeeder', '--force' => true]);

        $this->info('更新菜单权限');
        Artisan::call('app:update-menu-command');
    }

    /**
     * 更新客户照片数据（幂等：只更新 photo_type_id 为空的记录）
     */
    private function updateCustomerPhotosData(): void
    {
        DB::table('customer_photos')
            ->whereNull('photo_type_id')
            ->orWhere('photo_type_id', 0)
            ->update(['photo_type_id' => 1]);
    }
}
