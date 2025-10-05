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
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('cid')->comment('对外显示编号');
            $table->unsignedTinyInteger('material_type')->comment('素材类型');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('分类父编号');
            $table->string('name')->comment('分类名');
            $table->string('description')->nullable()->comment('分类描述');
            $table->integer('ranking')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_enabled')->default(1)->comment('是否启用分类');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('mid')->comment('对外显示编号');
            $table->unsignedBigInteger('material_category_id')->nullable()->comment('素材分类');
            $table->unsignedInteger('type')->nullable()->comment('素材类型');
            $table->string('title')->nullable()->comment('素材标题');
            $table->string('thumb')->nullable()->comment('素材封面图的缩略图或视频封面的缩略图');
            $table->string('cover_image')->nullable()->comment('素材封面图或视频封面图');
            $table->string('cover_video')->nullable()->comment('素材封面视频');
            $table->string('summary')->nullable()->comment('素材摘要');
            $table->text('content')->nullable()->comment('素材内容');
            $table->integer('ranking')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_share_disabled')->default(0)->comment('禁止分享状态');
            $table->unsignedTinyInteger('is_enabled_share_link')->default(0)->comment('是否启用轨迹链');
            $table->unsignedTinyInteger('is_enabled')->default(1)->comment('是否启用素材');
            $table->unsignedBigInteger('creator_id')->comment('创建人');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id')->comment('素材编号');
            $table->unsignedBigInteger('share')->default(0)->comment('分享次数');
            $table->unsignedBigInteger('pv')->default(0)->comment('浏览次数');
            $table->unsignedBigInteger('forward')->default(0)->comment('转发次数');
            $table->unsignedBigInteger('count')->default(0)->comment('获客数量');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sid')->comment('对外显示编号');
            $table->unsignedBigInteger('material_id')->comment('素材编号');
            $table->unsignedBigInteger('share_user_id')->comment('分享人');
            $table->unsignedBigInteger('share_time')->comment('分享时间');
            $table->unsignedBigInteger('view_count')->comment('查看次数');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_collect_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collector_id')->comment('收藏者');
            $table->unsignedBigInteger('collect_material_id')->comment('收藏素材');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_visit_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id')->comment('素材编号');
            $table->unsignedBigInteger('material_share_id')->comment('分享编号');
            $table->unsignedBigInteger('visit_user_id')->comment('查看人');
            $table->string('visit_user_ip')->comment('查看人IP');
            $table->string('visit_user_ua')->comment('查看人浏览器UA');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_categories');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('material_statistics');
        Schema::dropIfExists('material_shares');
        Schema::dropIfExists('user_collect_materials');
        Schema::dropIfExists('material_visit_records');
    }
};
