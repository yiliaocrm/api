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
        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->string('page')->index()->comment('适配页面');
            $table->string('name')->comment('场景名称');
            $table->boolean('public')->default(false)->comment('是否公用');
            $table->text('config')->nullable()->comment('场景配置');
            $table->string('type')->default('system')->comment('场景类型');
            $table->unsignedInteger('create_user_id')->comment('创建人');
            $table->timestamps();
            $table->comment('存储自定义场景');
        });
        Schema::create('scene_fields', function (Blueprint $table) {
            $table->id();
            $table->string('page')->index()->comment('适配页面');
            $table->string('name')->comment('字段名称');
            $table->string('api')->nullable()->comment('API地址');
            $table->string('table')->comment('表名');
            $table->string('field')->comment('字段');
            $table->string('field_alias')->nullable()->comment('字段别名(用于解决同名字段冲突)');
            $table->string('field_type')->comment('字段类型');
            $table->string('component')->comment('前端组件');
            $table->text('component_params')->nullable()->comment('前端组件参数');
            $table->text('operators')->comment('操作符列表');
            $table->text('query_config')->nullable()->comment('查询配置');
            $table->string('keyword')->nullable()->comment('搜索关键词');
            $table->comment('场景化搜索字段配置表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('scenes');
        Schema::dropIfExists('scene_fields');
    }
};
