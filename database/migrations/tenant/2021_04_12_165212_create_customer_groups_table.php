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
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分群名称');
            $table->bigInteger('category_id')->unsigned()->comment('分类ID');
            $table->enum('type', ['dynamic', 'static', 'sql'])->comment('分群类型');
            $table->text('remark')->nullable()->comment('分群备注');
            $table->integer('count')->default(0)->comment('人数');
            $table->text('filter_rule')->nullable()->comment('筛选规则');
            $table->text('exclude_rule')->nullable()->comment('排除规则');
            $table->text('sql')->nullable()->comment('SQL语句');
            $table->dateTime('last_execute_time')->nullable()->comment('最后一次执行时间');
            $table->integer('create_user_id')->unsigned()->comment('创建人员');
            $table->unsignedTinyInteger('processing')->default(0)->comment('是否正在计算中');
            $table->timestamps();
            $table->comment('顾客分群表');
        });
        Schema::create('customer_group_details', function (Blueprint $table) {
            $table->uuid('customer_id');
            $table->bigInteger('customer_group_id')->unsigned()->index();
            $table->timestamps();
            $table->comment('顾客分群明细表');
            $table->engine = 'MyISAM';
            $table->index(['customer_id', 'customer_group_id']);
        });
        Schema::create('customer_group_fields', function (Blueprint $table) {
            $table->id();
            $table->string('table')->comment('业务表');
            $table->string('field')->comment('业务字段');
            $table->string('field_type')->comment('字段类型');
            $table->string('table_name')->comment('前端展示业务表中文名称');
            $table->string('field_name')->comment('前端展示业务字段中文名称');
            $table->unsignedTinyInteger('auto_join')->default(1)->comment('是否自动连表');
            $table->text('query_config')->nullable()->comment('查询配置');
            $table->string('keyword')->nullable()->comment('搜索关键词');
            $table->string('api')->nullable()->comment('API地址');
            $table->string('component')->comment('前端组件');
            $table->string('component_params')->nullable()->comment('前端组件参数');
            $table->text('operators')->comment('操作符');
            $table->text('sql')->nullable()->comment('SQL参数');
            $table->comment('顾客分群字段表');
        });
        Schema::create('customer_group_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->integer('sort')->default(0)->comment('排序');
            $table->enum('scope', ['all', 'departments', 'users'])->comment('可见范围:all所有,departments部门,users用户');
            $table->timestamps();
            $table->comment('客户分群分类表');
        });
        Schema::create('customer_group_category_scopes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('category_id')->unsigned()->index()->comment('分类ID');
            $table->string('scopeable_type')->comment('可见范围类型');
            $table->unsignedBigInteger('scopeable_id')->comment('可见范围ID');
            $table->index(['scopeable_type', 'scopeable_id'], 'category_scopes_morphs_index');
            $table->timestamps();
            $table->comment('客户分群分类可见范围表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('customer_group_details');
        Schema::dropIfExists('customer_group_fields');
        Schema::dropIfExists('customer_group_categories');
    }
};
