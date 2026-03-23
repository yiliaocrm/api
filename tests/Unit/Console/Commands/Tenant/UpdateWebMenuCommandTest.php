<?php

namespace Tests\Unit\Console\Commands\Tenant;

use App\Console\Commands\Tenant\UpdateWebMenuCommand;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Tests\TestCase;

class UpdateWebMenuCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Artisan::clearResolvedInstance('artisan');
        $this->app->forgetInstance(TenantContract::class);

        parent::tearDown();
    }

    public function test_it_outputs_start_success_and_finish_messages_for_current_tenant(): void
    {
        $this->bindCurrentTenant('测试租户');

        Artisan::shouldReceive('call')
            ->once()
            ->with('db:seed', [
                '--class' => 'Database\\Seeders\\Tenant\\WebMenuTableSeeder',
                '--force' => true,
            ])
            ->andReturn(0);

        $command = new TestableUpdateWebMenuCommand;

        $command->handle();

        $this->assertSame([
            ['info', '[测试租户]开始更新Web菜单配置...'],
            ['info', '[测试租户]Web菜单配置更新成功'],
            ['info', '[测试租户]Web菜单配置更新结束'],
        ], $command->messages);
    }

    public function test_it_outputs_finish_message_when_update_throws_exception(): void
    {
        $this->bindCurrentTenant('测试租户');

        Artisan::shouldReceive('call')
            ->once()
            ->with('db:seed', [
                '--class' => 'Database\\Seeders\\Tenant\\WebMenuTableSeeder',
                '--force' => true,
            ])
            ->andThrow(new Exception('seed failed'));

        $command = new TestableUpdateWebMenuCommand;

        $command->handle();

        $this->assertSame([
            ['info', '[测试租户]开始更新Web菜单配置...'],
            ['error', '[测试租户]Web菜单配置更新失败: seed failed'],
            ['info', '[测试租户]Web菜单配置更新结束'],
        ], $command->messages);
    }

    private function bindCurrentTenant(string $name): void
    {
        $this->app->instance(TenantContract::class, new FakeTenant($name));
    }
}

class TestableUpdateWebMenuCommand extends UpdateWebMenuCommand
{
    public array $messages = [];

    public function info($string, $verbosity = null): void
    {
        $this->messages[] = ['info', $string];
    }

    public function error($string, $verbosity = null): void
    {
        $this->messages[] = ['error', $string];
    }
}

class FakeTenant implements TenantContract
{
    public function __construct(private string $name) {}

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): string
    {
        return 'tenant-id';
    }

    public function getInternal(string $key)
    {
        return null;
    }

    public function setInternal(string $key, $value)
    {
        return null;
    }

    public function run(callable $callback)
    {
        return $callback($this);
    }

    public function getAttribute(string $key): mixed
    {
        return $key === 'name' ? $this->name : null;
    }
}
