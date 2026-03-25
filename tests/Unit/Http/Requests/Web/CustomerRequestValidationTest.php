<?php

namespace Tests\Unit\Http\Requests\Web;

use App\Http\Requests\Web\CustomerRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class CustomerRequestValidationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_create_rules_keep_phones_relation_id_key(): void
    {
        $rules = $this->callProtectedRuleMethod(new CustomerRequest, 'getCreateRules');

        $this->assertArrayHasKey('phones.*.relation_id', $rules);
        $this->assertArrayNotHasKey('phone.*.relation_id', $rules);
    }

    #[RunInSeparateProcess]
    public function test_update_rules_keep_phones_relation_id_key(): void
    {
        \Mockery::mock('alias:App\\Models\\Parameter')
            ->shouldReceive('query->get->mapWithKeys->toArray')
            ->andReturn([]);

        $user = \Mockery::mock(User::class);
        $user->shouldReceive('hasAnyAccess')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $rules = $this->callProtectedRuleMethod(new CustomerRequest, 'getUpdateRules');

        $this->assertArrayHasKey('phones.*.relation_id', $rules);
        $this->assertArrayNotHasKey('phone.*.relation_id', $rules);
    }

    private function callProtectedRuleMethod(CustomerRequest $request, string $method): array
    {
        $reflection = new \ReflectionClass($request);
        $methodRef = $reflection->getMethod($method);
        $methodRef->setAccessible(true);

        return $methodRef->invoke($request);
    }
}
