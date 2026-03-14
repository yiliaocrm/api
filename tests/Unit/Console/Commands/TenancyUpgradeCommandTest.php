<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\TenancyUpgradeCommand;
use App\Upgrades\Versions\BaseVersion;
use ReflectionMethod;
use Tests\TestCase;

class TenancyUpgradeCommandTest extends TestCase
{
    private TenancyUpgradeCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new TenancyUpgradeCommand;
    }

    private function invoke(string $method, ...$args): mixed
    {
        $ref = new ReflectionMethod($this->command, $method);

        return $ref->invoke($this->command, ...$args);
    }

    // ========== getVersions 排序 ==========

    public function test_versions_are_sorted_by_version_compare(): void
    {
        $versions = $this->invoke('getVersions');

        $versionStrings = array_map(fn ($v) => $v->version(), $versions);

        $sorted = $versionStrings;
        usort($sorted, 'version_compare');

        $this->assertEquals($sorted, $versionStrings, '版本应按 version_compare 排序');
    }

    // ========== filterPendingVersions ==========

    public function test_filter_new_versions_only(): void
    {
        $versions = $this->createMockVersions(['1.0.1', '1.0.2', '1.0.3']);

        $pending = $this->invoke('filterPendingVersions', $versions, '1.0.2', null, false, []);

        $pendingVersions = array_map(fn ($v) => $v->version(), $pending);
        $this->assertEquals(['1.0.3'], array_values($pendingVersions));
    }

    public function test_filter_with_version_option(): void
    {
        $versions = $this->createMockVersions(['1.0.1', '1.0.2', '1.0.3']);

        $pending = $this->invoke('filterPendingVersions', $versions, '1.0.0', '1.0.2', false, []);

        $pendingVersions = array_map(fn ($v) => $v->version(), $pending);
        $this->assertEquals(['1.0.2'], array_values($pendingVersions));
    }

    public function test_filter_includes_versions_with_failed_logs(): void
    {
        $versions = $this->createMockVersions(['1.0.1', '1.0.2', '1.0.3']);

        // 1.0.2 有失败记录，即使 currentVersion=1.0.2 也应该包含
        $failedLogs = ['1.0.2|tenant|abc'];

        $pending = $this->invoke('filterPendingVersions', $versions, '1.0.2', null, false, $failedLogs);

        $pendingVersions = array_map(fn ($v) => $v->version(), $pending);
        $this->assertContains('1.0.2', $pendingVersions, '有失败记录的版本应被包含');
        $this->assertContains('1.0.3', $pendingVersions, '新版本也应被包含');
    }

    // ========== shouldRunPhase ==========

    public function test_should_run_phase_for_new_version(): void
    {
        $this->assertTrue(
            $this->invoke('shouldRunPhase', '1.0.3', 'central', '1.0.2', false, [])
        );
    }

    public function test_should_not_run_phase_for_completed_version(): void
    {
        $this->assertFalse(
            $this->invoke('shouldRunPhase', '1.0.2', 'central', '1.0.2', false, [])
        );
    }

    public function test_should_run_phase_with_force(): void
    {
        $this->assertTrue(
            $this->invoke('shouldRunPhase', '1.0.1', 'central', '1.0.2', true, [])
        );
    }

    public function test_should_run_phase_with_failed_log(): void
    {
        $failedLogs = ['1.0.2|central|'];

        $this->assertTrue(
            $this->invoke('shouldRunPhase', '1.0.2', 'central', '1.0.2', false, $failedLogs)
        );
    }

    // ========== shouldRunTenantUp ==========

    public function test_should_run_tenant_up_when_tenant_version_lower(): void
    {
        $tenant = $this->createMockTenant('test', '1.0.1');

        $this->assertTrue(
            $this->invoke('shouldRunTenantUp', '1.0.2', $tenant, [])
        );
    }

    public function test_should_not_run_tenant_up_when_tenant_version_equal(): void
    {
        $tenant = $this->createMockTenant('test', '1.0.2');

        $this->assertFalse(
            $this->invoke('shouldRunTenantUp', '1.0.2', $tenant, [])
        );
    }

    public function test_should_run_tenant_up_with_failed_log(): void
    {
        $tenant = $this->createMockTenant('test', '1.0.2');
        $failedLogs = ['1.0.2|tenant|test'];

        $this->assertTrue(
            $this->invoke('shouldRunTenantUp', '1.0.2', $tenant, $failedLogs)
        );
    }

    // ========== force 约束 ==========

    public function test_force_requires_ver_option(): void
    {
        $this->artisan('app:tenancy-upgrade-command', ['--force' => true])
            ->expectsOutputToContain('--force 必须配合 --ver 使用')
            ->assertFailed();
    }

    // ========== filterPendingVersions 边界情况 ==========

    public function test_filter_force_with_specific_version(): void
    {
        $versions = $this->createMockVersions(['1.0.1', '1.0.2', '1.0.3']);

        // force + 指定版本：即使 currentVersion 已经超过也能选中
        $pending = $this->invoke('filterPendingVersions', $versions, '1.0.3', '1.0.1', true, []);

        $pendingVersions = array_map(fn ($v) => $v->version(), $pending);
        $this->assertEquals(['1.0.1'], array_values($pendingVersions));
    }

    // ========== 辅助方法 ==========

    private function createMockVersions(array $versionNumbers): array
    {
        return array_map(function ($v) {
            return new class($v) extends BaseVersion
            {
                public function __construct(private string $v) {}

                public function version(): string
                {
                    return $this->v;
                }
            };
        }, $versionNumbers);
    }

    private function createMockTenant(string $id, string $version): object
    {
        return new class($id, $version)
        {
            public string $id;

            public ?string $version;

            public function __construct(string $id, string $version)
            {
                $this->id = $id;
                $this->version = $version;
            }
        };
    }
}
