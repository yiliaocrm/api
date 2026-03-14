<?php

namespace Tests\Unit\Upgrades;

use App\Upgrades\Versions\BaseVersion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BaseVersionTest extends TestCase
{
    /**
     * 创建一个具体的 BaseVersion 子类用于测试
     */
    private function makeVersion(): BaseVersion
    {
        return new class extends BaseVersion
        {
            public function version(): string
            {
                return '0.0.1';
            }

            // 公开 protected 方法用于测试
            public function test_create_table_if_not_exists(string $table, \Closure $callback): void
            {
                $this->createTableIfNotExists($table, $callback);
            }

            public function test_add_column_if_not_exists(string $table, string $column, \Closure $callback): void
            {
                $this->addColumnIfNotExists($table, $column, $callback);
            }

            public function test_drop_column_if_exists(string $table, string $column): void
            {
                $this->dropColumnIfExists($table, $column);
            }

            public function test_drop_table_if_exists(string $table): void
            {
                $this->dropTableIfExists($table);
            }
        };
    }

    public function test_default_methods_are_empty(): void
    {
        $version = $this->makeVersion();

        // 三个方法不抛异常即为通过
        $version->centralUp();
        $version->tenantUp();
        $version->globalUp();

        $this->assertTrue(true);
    }

    public function test_create_table_if_not_exists_skips_when_table_exists(): void
    {
        Schema::shouldReceive('hasTable')->with('existing_table')->once()->andReturn(true);
        Schema::shouldReceive('create')->never();

        $version = $this->makeVersion();
        $version->testCreateTableIfNotExists('existing_table', function (Blueprint $table) {
            $table->id();
        });
    }

    public function test_create_table_if_not_exists_creates_when_table_not_exists(): void
    {
        Schema::shouldReceive('hasTable')->with('new_table')->once()->andReturn(false);
        Schema::shouldReceive('create')->once();

        $version = $this->makeVersion();
        $version->testCreateTableIfNotExists('new_table', function (Blueprint $table) {
            $table->id();
        });
    }

    public function test_add_column_if_not_exists_skips_when_column_exists(): void
    {
        Schema::shouldReceive('hasColumn')->with('users', 'email')->once()->andReturn(true);
        Schema::shouldReceive('table')->never();

        $version = $this->makeVersion();
        $version->testAddColumnIfNotExists('users', 'email', function (Blueprint $table) {
            $table->string('email');
        });
    }

    public function test_add_column_if_not_exists_adds_when_column_not_exists(): void
    {
        Schema::shouldReceive('hasColumn')->with('users', 'phone')->once()->andReturn(false);
        Schema::shouldReceive('table')->once();

        $version = $this->makeVersion();
        $version->testAddColumnIfNotExists('users', 'phone', function (Blueprint $table) {
            $table->string('phone');
        });
    }

    public function test_drop_column_if_exists_skips_when_column_not_exists(): void
    {
        Schema::shouldReceive('hasColumn')->with('users', 'old_col')->once()->andReturn(false);
        Schema::shouldReceive('table')->never();

        $version = $this->makeVersion();
        $version->testDropColumnIfExists('users', 'old_col');
    }

    public function test_drop_column_if_exists_drops_when_column_exists(): void
    {
        Schema::shouldReceive('hasColumn')->with('users', 'old_col')->once()->andReturn(true);
        Schema::shouldReceive('table')->once();

        $version = $this->makeVersion();
        $version->testDropColumnIfExists('users', 'old_col');
    }
}
