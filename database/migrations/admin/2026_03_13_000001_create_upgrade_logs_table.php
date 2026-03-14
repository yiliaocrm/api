<?php

declare(strict_types=1);

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
        Schema::create('upgrade_logs', function (Blueprint $table) {
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upgrade_logs');
    }
};
