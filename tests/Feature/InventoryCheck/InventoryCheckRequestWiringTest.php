<?php

namespace Tests\Feature\InventoryCheck;

use App\Http\Controllers\Web\InventoryCheckController;
use App\Http\Requests\Web\InventoryCheckRequest;
use ReflectionMethod;
use Tests\TestCase;

class InventoryCheckRequestWiringTest extends TestCase
{
    public function test_controller_uses_unified_inventory_check_request(): void
    {
        foreach (['manage', 'check', 'create', 'update', 'remove'] as $method) {
            $reflection = new ReflectionMethod(InventoryCheckController::class, $method);
            $parameters = $reflection->getParameters();

            $this->assertCount(1, $parameters, "{$method} should have exactly one request parameter");
            $this->assertSame(
                InventoryCheckRequest::class,
                $parameters[0]->getType()?->getName(),
                "{$method} should use the unified InventoryCheckRequest"
            );
        }
    }
}
