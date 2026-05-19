<?php

namespace Tests\Feature\Web;

use App\Http\Controllers\Web\WorkbenchController;
use App\Http\Requests\Web\WorkbenchRequest;
use App\Models\User;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkbenchMenuTest extends TestCase
{
    private string $originalTablePrefix = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        Route::get('/workbench/menu', [WorkbenchController::class, 'menu']);

        $this->originalTablePrefix = DB::connection()->getTablePrefix();
        DB::connection()->setTablePrefix('cy_');
        Carbon::setTestNow('2026-05-11 09:00:00');
        $this->createTables();
        $this->seedMenus();
        $this->seedReceptionRows();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('reception');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('users');
        DB::connection()->setTablePrefix($this->originalTablePrefix);
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_menu_includes_consultant_when_user_has_consultant_index_permission(): void
    {
        $this->actingAsWorkbenchUser([
            'workbench.consultant.index' => true,
        ]);

        $payload = $this->dispatchMenu();

        $this->assertSame(200, $payload['code']);
        $this->assertContains('咨询管理', array_column($payload['data'], 'title'));
    }

    public function test_menu_includes_consultant_when_user_has_consultant_create_permission(): void
    {
        $this->actingAsWorkbenchUser([
            'workbench.consultant.create' => true,
        ]);

        $payload = $this->dispatchMenu();

        $this->assertSame(200, $payload['code']);
        $this->assertContains('咨询管理', array_column($payload['data'], 'title'));
    }

    public function test_menu_excludes_consultant_without_consultant_permissions(): void
    {
        $this->actingAsWorkbenchUser([
            'workbench.today' => true,
        ]);

        $payload = $this->dispatchMenu();

        $this->assertSame(200, $payload['code']);
        $this->assertNotContains('咨询管理', array_column($payload['data'], 'title'));
        $this->assertContains('今日工作', array_column($payload['data'], 'title'));
    }

    public function test_super_user_receives_all_workbench_menus(): void
    {
        $this->actingAsWorkbenchUser([
            'superuser' => true,
        ]);

        $payload = $this->dispatchMenu();

        $this->assertSame(200, $payload['code']);
        $this->assertSame(['今日工作', '咨询管理'], array_column($payload['data'], 'title'));
    }

    public function test_consultant_menu_count_is_integer(): void
    {
        $this->actingAsWorkbenchUser([
            'workbench.consultant.index' => true,
            'consultant.view.all' => true,
        ]);

        $payload = $this->dispatchMenu();
        $consultantMenu = collect($payload['data'])->firstWhere('title', '咨询管理');

        $this->assertNotNull($consultantMenu);
        $this->assertIsInt($consultantMenu['count']);
        $this->assertSame(1, $consultantMenu['count']);
    }

    private function dispatchMenu(): array
    {
        $request = Request::create(
            '/workbench/menu',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        app()->instance('request', $request);
        $response = app(WorkbenchController::class)->menu(
            WorkbenchRequest::createFrom($request)
        );

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function actingAsWorkbenchUser(array $permissions): void
    {
        DB::table('users')->insert([
            'id' => 10,
            'name' => '当前用户',
            'email' => 'current@example.com',
            'password' => 'secret',
            'department_id' => 1,
            'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
        ]);

        auth()->setUser(new class($permissions) extends User implements \Illuminate\Contracts\Auth\Authenticatable
        {
            use Authenticatable;

            public function __construct(private readonly array $testPermissions)
            {
                $this->id = 10;
                $this->department_id = 1;
            }

            public function hasAnyAccess($permissions): bool
            {
                foreach ((array) $permissions as $permission) {
                    if (($this->testPermissions[$permission] ?? false) === true) {
                        return true;
                    }
                }

                return false;
            }

            public function isSuperUser(): bool
            {
                return ($this->testPermissions['superuser'] ?? false) === true;
            }

            public function getMergedPermissions(): array
            {
                return $this->testPermissions;
            }

            public function getConsultantViewUsersPermission(): array
            {
                return [$this->id];
            }
        });
    }

    private function seedMenus(): void
    {
        DB::table('menus')->insert([
            [
                'id' => 1,
                'parentid' => 0,
                'title' => '工作台',
                'permission' => '',
                'type' => 'web',
                'menu_type' => 'menu',
                'order' => 1,
                'created_at' => '2026-05-11 00:00:00',
                'updated_at' => '2026-05-11 00:00:00',
            ],
            [
                'id' => 2,
                'parentid' => 1,
                'title' => '今日工作',
                'permission' => 'workbench.today',
                'type' => 'web',
                'menu_type' => 'menu',
                'order' => 1,
                'created_at' => '2026-05-11 00:00:00',
                'updated_at' => '2026-05-11 00:00:00',
            ],
            [
                'id' => 3,
                'parentid' => 1,
                'title' => '咨询管理',
                'permission' => 'workbench.consultant',
                'type' => 'web',
                'menu_type' => 'menu',
                'order' => 2,
                'created_at' => '2026-05-11 00:00:00',
                'updated_at' => '2026-05-11 00:00:00',
            ],
            [
                'id' => 4,
                'parentid' => 3,
                'title' => '查看记录',
                'permission' => 'workbench.consultant.index',
                'type' => 'web',
                'menu_type' => 'button',
                'order' => 1,
                'created_at' => '2026-05-11 00:00:00',
                'updated_at' => '2026-05-11 00:00:00',
            ],
            [
                'id' => 5,
                'parentid' => 3,
                'title' => '咨询登记',
                'permission' => 'workbench.consultant.create',
                'type' => 'web',
                'menu_type' => 'button',
                'order' => 2,
                'created_at' => '2026-05-11 00:00:00',
                'updated_at' => '2026-05-11 00:00:00',
            ],
        ]);
    }

    private function seedReceptionRows(): void
    {
        DB::table('reception')->insert([
            [
                'id' => 'reception-today',
                'consultant' => 10,
                'ek_user' => null,
                'created_at' => '2026-05-11 10:00:00',
                'updated_at' => '2026-05-11 10:00:00',
            ],
            [
                'id' => 'reception-yesterday',
                'consultant' => 10,
                'ek_user' => null,
                'created_at' => '2026-05-10 10:00:00',
                'updated_at' => '2026-05-10 10:00:00',
            ],
        ]);
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->json('permissions')->nullable();
            $table->integer('department_id')->nullable();
        });

        Schema::create('menus', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('parentid')->default(0);
            $table->string('title')->nullable();
            $table->string('permission')->nullable();
            $table->string('type')->default('web');
            $table->string('menu_type')->default('menu');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('reception', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->integer('consultant')->nullable();
            $table->integer('ek_user')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }
}
