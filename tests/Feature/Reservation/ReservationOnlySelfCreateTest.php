<?php

namespace Tests\Feature\Reservation;

use App\Http\Controllers\Web\ReservationController;
use App\Models\User;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationOnlySelfCreateTest extends TestCase
{
    private ?AuthenticatableContract $loginUser = null;

    private bool $hadInstallLock = false;

    protected function setUp(): void
    {
        $installLock = $this->installLockPath();
        $this->hadInstallLock = file_exists($installLock);
        if (! $this->hadInstallLock) {
            file_put_contents($installLock, 'testing');
        }

        parent::setUp();

        $this->withoutMiddleware();
        $this->assertApplicationRouteExists(
            'reservation/create',
            'POST',
            ReservationController::class.'@create'
        );

        $this->dropFixtureTables();
        $this->createTables();
        $this->seedBaseData();
    }

    protected function tearDown(): void
    {
        auth()->logout();
        $this->dropFixtureTables();
        $installLock = $this->installLockPath();
        if (! $this->hadInstallLock && is_file($installLock)) {
            unlink($installLock);
        }

        parent::tearDown();
    }

    public function test_create_allows_customer_owned_by_current_developer_when_only_self_create_enabled(): void
    {
        $this->actingAsReservationUser(101);

        DB::table('customer')->insert([
            'id' => 'customer-owned-by-current-user',
            'name' => '当前开发员顾客',
            'ascription' => 101,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->dispatchCreate('customer-owned-by-current-user');

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(
            'customer-owned-by-current-user',
            DB::table('reservation')->value('customer_id')
        );
    }

    public function test_create_rejects_customer_owned_by_another_developer_when_only_self_create_enabled(): void
    {
        $this->actingAsReservationUser(101);

        DB::table('customer')->insert([
            'id' => 'customer-owned-by-another-user',
            'name' => '其他开发员顾客',
            'ascription' => 202,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->dispatchCreate('customer-owned-by-another-user');

        $this->assertSame(400, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString(
            '顾客不是您的，没有权限操作!',
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
        $this->assertSame(0, DB::table('reservation')->count());
    }

    private function dispatchCreate(string $customerId): array
    {
        $request = Request::create(
            '/reservation/create',
            'POST',
            [
                'customer_id' => $customerId,
                'medium_id' => 2,
                'type' => 1,
                'date' => '2026-05-10',
                'department_id' => 1,
                'items' => [1],
                'remark' => '测试咨询备注',
            ],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );

        app()->instance('request', $request);
        if ($this->loginUser) {
            auth()->shouldUse('web');
            auth('web')->setUser($this->loginUser);
        }

        $response = app('router')->dispatch($request);
        $decoded = json_decode($response->getContent(), true);

        $this->assertIsArray(
            $decoded,
            sprintf(
                'Expected JSON response, got HTTP %s: %s',
                $response->getStatusCode(),
                mb_substr($response->getContent(), 0, 500)
            )
        );

        return $decoded;
    }

    private function installLockPath(): string
    {
        return dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'install.lock';
    }

    private function actingAsReservationUser(int $id): void
    {
        $user = new class($id) extends User implements AuthenticatableContract
        {
            use Authenticatable;

            public function __construct(int $id)
            {
                $this->id = $id;
                $this->name = 'U'.$id;
            }
        };

        $this->loginUser = $user;
        auth()->shouldUse('web');
        auth('web')->setUser($user);
    }

    private function createTables(): void
    {
        Schema::create('parameters', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->unsignedInteger('ascription')->default(0);
            $table->timestamps();
        });

        Schema::create('medium', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('reservation_type', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('item', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('reservation', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->unsignedInteger('medium_id');
            $table->unsignedInteger('type');
            $table->date('date');
            $table->unsignedInteger('department_id');
            $table->json('items')->nullable();
            $table->text('remark')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('ascription');
            $table->unsignedInteger('user_id');
            $table->dateTime('time')->nullable();
            $table->timestamps();
        });

        Schema::create('reservation_items', function (Blueprint $table): void {
            $table->uuid('reservation_id');
            $table->unsignedInteger('item_id');
        });

        Schema::create('customer_life_cycle', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('cycle_type');
            $table->uuid('cycle_id');
            $table->string('name')->nullable();
            $table->uuid('customer_id');
            $table->timestamps();
        });

        Schema::create('customer_log', function (Blueprint $table): void {
            $table->id();
            $table->string('logable_type');
            $table->uuid('logable_id');
            $table->uuid('customer_id');
            $table->string('action', 100)->nullable();
            $table->unsignedInteger('user_id')->default(0);
            $table->text('original')->nullable();
            $table->text('dirty')->nullable();
            $table->longText('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_talk', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('talk_type');
            $table->uuid('talk_id');
            $table->string('name')->nullable();
            $table->uuid('customer_id');
            $table->timestamps();
        });

        Schema::create('customer_items', function (Blueprint $table): void {
            $table->id();
            $table->string('itemable_type');
            $table->uuid('itemable_id');
            $table->unsignedInteger('item_id');
            $table->uuid('customer_id');
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('parameters')->insert([
            [
                'name' => 'reservation_only_self_create',
                'value' => 'true',
                'type' => 'boolean',
            ],
            [
                'name' => 'reservation_only_create_once',
                'value' => 'false',
                'type' => 'boolean',
            ],
            [
                'name' => 'reservation_allow_multiple_item',
                'value' => 'false',
                'type' => 'boolean',
            ],
        ]);

        DB::table('medium')->insert(['id' => 2, 'name' => '测试来源']);
        DB::table('reservation_type')->insert(['id' => 1, 'name' => '测试类型']);
        DB::table('department')->insert(['id' => 1, 'name' => '测试科室']);
        DB::table('item')->insert(['id' => 1, 'name' => '测试项目']);
    }

    private function dropFixtureTables(): void
    {
        Schema::dropIfExists('customer_items');
        Schema::dropIfExists('customer_talk');
        Schema::dropIfExists('customer_log');
        Schema::dropIfExists('customer_life_cycle');
        Schema::dropIfExists('reservation_items');
        Schema::dropIfExists('reservation');
        Schema::dropIfExists('item');
        Schema::dropIfExists('department');
        Schema::dropIfExists('reservation_type');
        Schema::dropIfExists('medium');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('parameters');
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
