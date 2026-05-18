<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Web\AuthController as WebAuthController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Tenancy;
use Tests\TestCase;

class AuthConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->createTables();
        $this->seedBaseData();
        $this->bindTenantContext();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tenant_login_banners');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('admin_parameters');
        Schema::dropIfExists('parameters');

        parent::tearDown();
    }

    public function test_web_auth_config_does_not_return_consultant_allow_reception(): void
    {
        $this->assertApplicationRouteExists('auth/config', 'GET', WebAuthController::class.'@getConfig');

        $response = (new WebAuthController)->getConfig();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertArrayNotHasKey('consultant_allow_reception', $payload['data']);
    }

    public function test_api_auth_config_does_not_return_consultant_allow_reception(): void
    {
        $response = (new ApiAuthController)->config();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertArrayNotHasKey('consultant_allow_reception', $payload['data']);
    }

    private function bindTenantContext(): void
    {
        Event::fake();

        $tenant = new class extends Model implements Tenant
        {
            protected $guarded = [];

            protected $table = 'tenants';

            public function __construct()
            {
                parent::__construct([
                    'id' => 'tenant-demo',
                    'expire_date' => '2026-12-31',
                ]);
            }

            public function getTenantKeyName(): string
            {
                return 'id';
            }

            public function getTenantKey()
            {
                return $this->getAttribute('id');
            }

            public function getInternal(string $key)
            {
                return $this->getAttribute($key);
            }

            public function setInternal(string $key, $value)
            {
                $this->setAttribute($key, $value);

                return $this;
            }

            public function run(callable $callback)
            {
                return $callback($this);
            }
        };

        $tenancy = new Tenancy;
        $tenancy->tenant = $tenant;
        $tenancy->initialized = true;

        app()->instance(Tenant::class, $tenant);
        app()->instance(Tenancy::class, $tenancy);
    }

    private function createTables(): void
    {
        Schema::create('parameters', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_parameters', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_login_banners', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('image_path');
            $table->string('link_url')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('disabled')->default(false);
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('parameters')->insert([
            ['name' => 'watermark_enable', 'value' => 'false', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'customer_phone_click2show', 'value' => 'true', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cywebos_hospital_name', 'value' => '测试机构', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultant_allow_reception', 'value' => 'true', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cashier_allow_modify', 'value' => 'false', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cywebos_force_enable_google_authenticator', 'value' => 'false', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cywebos_enable_item_product_type_sync', 'value' => 'true', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reservation_allow_modify_medium', 'value' => 'false', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'customer_allow_modify_medium', 'value' => 'false', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cywebos_apps_autoload', 'value' => 'false', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('admin_parameters')->insert([
            ['name' => 'oem_help_url', 'value' => 'https://example.com/help', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'oem_app_qrcode', 'value' => '/qrcode.png', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'oem_system_name', 'value' => 'HIS', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'oem_system_logo', 'value' => '/logo.png', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'oem_service_qrcode', 'value' => '/service.png', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'oem_service_phone', 'value' => '123456', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'oem_service_description', 'value' => 'desc', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'sql_group_tfa', 'value' => 'false', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reverb_host', 'value' => '127.0.0.1', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reverb_port', 'value' => '8080', 'type' => 'number', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reverb_scheme', 'value' => 'http', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reverb_app_id', 'value' => 'app-id', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reverb_app_key', 'value' => 'app-key', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reverb_app_secret', 'value' => 'app-secret', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('stores')->insert([
            'id' => 1,
            'name' => '默认门店',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tenant_login_banners')->insert([
            'title' => '欢迎使用',
            'image_path' => '/banner.png',
            'link_url' => null,
            'order' => 1,
            'disabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertApplicationRouteExists(string $uri, string $method, string $action): void
    {
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
}
