<?php

namespace Tests\Feature\CashierRetail;

use App\Http\Controllers\Web\CashierRetailController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierRetailFillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('cashier-retail/fill', 'GET', CashierRetailController::class.'@fill');
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('reception');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    public function test_fill_returns_type_one_for_first_reception_customer(): void
    {
        DB::table('customer')->insert([
            'id' => 'customer-first',
            'name' => '首诊顾客',
            'medium_id' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reception')->insert([
            'id' => 'reception-1',
            'customer_id' => 'customer-first',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->dispatchFill(['customer_id' => 'customer-first']);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['type']);
        $this->assertSame(11, $payload['data']['medium_id']);
    }

    public function test_fill_returns_type_two_when_customer_has_multiple_receptions(): void
    {
        DB::table('customer')->insert([
            'id' => 'customer-repeat',
            'name' => '复诊顾客',
            'medium_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reception')->insert([
            [
                'id' => 'reception-2',
                'customer_id' => 'customer-repeat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'reception-3',
                'customer_id' => 'customer-repeat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = $this->dispatchFill(['customer_id' => 'customer-repeat']);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['type']);
        $this->assertSame(22, $payload['data']['medium_id']);
    }

    private function dispatchFill(array $query): array
    {
        $request = Request::create('/cashier-retail/fill', 'GET', $query, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        app()->instance('request', $request);
        $response = app('router')->dispatch($request);

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createTables(): void
    {
        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->unsignedInteger('medium_id')->nullable();
            $table->timestamps();
        });

        Schema::create('reception', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->timestamps();
        });
    }

    private function assertApplicationRouteExists(string $uri, string $method, string $action): void
    {
        if (! $this->hasRoute($uri, $method, $action)) {
            require base_path('routes/web.php');
        }

        $this->assertTrue(
            $this->hasRoute($uri, $method, $action),
            sprintf('Missing app route [%s %s] => %s from routes/web.php', $method, $uri, $action)
        );
    }

    private function hasRoute(string $uri, string $method, string $action): bool
    {
        foreach (app('router')->getRoutes() as $route) {
            $routeAction = $route->getAction()['controller'] ?? $route->getActionName();
            if (
                $route->uri() === $uri
                && in_array($method, $route->methods(), true)
                && $routeAction === $action
            ) {
                return true;
            }
        }

        return false;
    }
}
