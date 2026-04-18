<?php

namespace Tests\Feature\CashierRetail;

use App\Enums\CashierRetailStatus;
use App\Http\Controllers\Web\CashierRetailController;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashierRetailRemoveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $this->assertApplicationRouteExists(
            'cashier-retail/remove',
            'GET',
            CashierRetailController::class.'@remove'
        );

        $this->dropFixtureTables();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        $this->dropFixtureTables();

        parent::tearDown();
    }

    public function test_remove_deletes_pending_cashier_retail_record_via_route_dispatch(): void
    {
        $retailId = (string) Str::uuid();

        DB::table('cashier_retail')->insert([
            'id' => $retailId,
            'status' => CashierRetailStatus::PENDING->value,
        ]);

        $request = Request::create('/cashier-retail/remove', 'GET', ['id' => $retailId], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = app(Kernel::class)->handle($request);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertDatabaseMissing('cashier_retail', ['id' => $retailId]);
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
        foreach (Route::getRoutes() as $route) {
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

    private function createTables(): void
    {
        Schema::create('cashier_retail', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedTinyInteger('status')->default(CashierRetailStatus::PENDING->value);
        });

        Schema::create('cashier_retail_detail', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('cashier_retail_id')->index();
        });
    }

    private function dropFixtureTables(): void
    {
        Schema::dropIfExists('cashier_retail_detail');
        Schema::dropIfExists('cashier_retail');
    }
}
