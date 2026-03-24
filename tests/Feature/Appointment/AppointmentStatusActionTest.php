<?php

namespace Tests\Feature\Appointment;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Api\AppointmentController as ApiAppointmentController;
use App\Http\Controllers\Web\AppointmentController;
use App\Http\Requests\Api\AppointmentRequest as ApiAppointmentRequest;
use App\Http\Requests\Web\AppointmentRequest as WebAppointmentRequest;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentStatusActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertApplicationRouteExists('appointment/create', 'POST', 'App\Http\Controllers\Web\AppointmentController@create');
        $this->assertApplicationRouteExists('appointment/update', 'POST', 'App\Http\Controllers\Web\AppointmentController@update');
        $this->assertApplicationRouteExists('api/appointment/create', 'POST', 'App\Http\Controllers\Api\AppointmentController@create');
        $this->assertApplicationRouteExists('appointment/arrival', 'GET', 'App\Http\Controllers\Web\AppointmentController@arrival');
        $this->createTables();
        $this->mockAuthUser();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('department');
        Schema::dropIfExists('room');
        Schema::dropIfExists('item');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_it_defaults_new_appointments_to_pending_confirm(): void
    {
        $request = WebAppointmentRequest::create('/appointment/create', 'POST', [
            'customer_id' => $this->seedCustomer(),
            'type' => 'coming',
            'date' => '2026-03-25',
            'start' => '2026-03-25 09:00:00',
            'end' => '2026-03-25 09:30:00',
            'department_id' => 1,
            'doctor_id' => 11,
            'consultant_id' => 12,
            'technician_id' => 13,
            'items' => [101],
            'room_id' => 1,
            'duration' => 30,
            'remark' => 'status default test',
        ]);
        $request->setRouteResolver(fn () => Route::getRoutes()->match(Request::create('/appointment/create', 'POST')));
        app()->instance('request', $request);

        $controller = new AppointmentController($this->mock(AppointmentService::class));
        $response = $controller->create($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertDatabaseHas('appointments', [
            'id' => $data['data']['id'],
            'status' => AppointmentStatus::PENDING_CONFIRM->value,
        ]);
    }

    public function test_it_does_not_reset_status_when_updating_existing_appointment(): void
    {
        $appointmentId = $this->seedAppointment(AppointmentStatus::PENDING_ARRIVAL);
        $request = WebAppointmentRequest::create('/appointment/update', 'POST', [
            'id' => $appointmentId,
            'type' => 'coming',
            'date' => '2026-03-25',
            'start' => '2026-03-25 09:30:00',
            'end' => '2026-03-25 10:00:00',
            'department_id' => 1,
            'doctor_id' => 11,
            'consultant_id' => 12,
            'technician_id' => 13,
            'items' => [101],
            'room_id' => 1,
            'duration' => 30,
            'remark' => 'update should keep status',
        ]);
        $request->setRouteResolver(fn () => Route::getRoutes()->match(Request::create('/appointment/update', 'POST')));
        app()->instance('request', $request);

        $controller = new AppointmentController($this->mock(AppointmentService::class));
        $response = $controller->update($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::PENDING_ARRIVAL->value,
        ]);
    }

    public function test_api_create_defaults_new_appointments_to_pending_confirm(): void
    {
        $request = ApiAppointmentRequest::create('/api/appointment/create', 'POST', [
            'customer_id' => $this->seedCustomer(),
            'type' => 'coming',
            'date' => '2026-03-25',
            'start' => '2026-03-25 09:00:00',
            'end' => '2026-03-25 09:30:00',
            'department_id' => 1,
            'doctor_id' => 11,
            'consultant_id' => 12,
            'technician_id' => 13,
            'items' => [101],
            'room_id' => 1,
            'duration' => 30,
            'remark' => 'api status default test',
        ]);
        $request->setRouteResolver(fn () => Route::getRoutes()->match(Request::create('/api/appointment/create', 'POST')));
        app()->instance('request', $request);

        $controller = new ApiAppointmentController($this->mock(AppointmentService::class));
        $response = $controller->create($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertDatabaseHas('appointments', [
            'id' => $data['data']['id'],
            'status' => AppointmentStatus::PENDING_CONFIRM->value,
        ]);
    }

    public function test_it_confirms_pending_confirm_appointments(): void
    {
        $appointmentId = $this->seedAppointment(AppointmentStatus::PENDING_CONFIRM);
        $this->assertTrue(
            $this->hasRoute('appointment/confirm', 'GET', 'App\Http\Controllers\Web\AppointmentController@confirm'),
            'Missing confirm route: GET appointment/confirm'
        );

        [$response, $data] = $this->dispatchJsonRequest('GET', '/appointment/confirm', ['id' => $appointmentId]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::PENDING_ARRIVAL->value,
        ]);
    }

    public function test_it_rejects_confirm_for_non_pending_confirm_appointments(): void
    {
        $appointmentId = $this->seedAppointment(AppointmentStatus::PENDING_ARRIVAL);
        $this->assertTrue(
            $this->hasRoute('appointment/confirm', 'GET', 'App\Http\Controllers\Web\AppointmentController@confirm'),
            'Missing confirm route: GET appointment/confirm'
        );

        [$response, $data] = $this->dispatchJsonRequest('GET', '/appointment/confirm', ['id' => $appointmentId]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(400, $data['code']);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::PENDING_ARRIVAL->value,
        ]);
    }

    public function test_it_marks_pending_arrival_appointments_as_arrived(): void
    {
        $appointmentId = $this->seedAppointment(AppointmentStatus::PENDING_ARRIVAL);

        [$response, $data] = $this->dispatchJsonRequest('GET', '/appointment/arrival', ['id' => $appointmentId]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $data['code']);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::ARRIVED->value,
        ]);

        $arrivalTime = DB::table('appointments')->where('id', $appointmentId)->value('arrival_time');
        $this->assertNotNull($arrivalTime);
    }

    public function test_it_rejects_arrival_for_non_pending_arrival_appointments(): void
    {
        $appointmentId = $this->seedAppointment(AppointmentStatus::PENDING_CONFIRM);

        [$response, $data] = $this->dispatchJsonRequest('GET', '/appointment/arrival', ['id' => $appointmentId]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(400, $data['code']);
        $this->assertSame('当前预约状态不允许确认到店', $data['msg']);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::PENDING_CONFIRM->value,
            'arrival_time' => null,
        ]);
    }

    private function seedCustomer(): string
    {
        $customerId = Str::uuid()->toString();
        DB::table('customer')->insert([
            'id' => $customerId,
            'name' => '顾客A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $customerId;
    }

    private function seedAppointment(AppointmentStatus $status): string
    {
        $appointmentId = Str::uuid()->toString();

        DB::table('appointments')->insert([
            'id' => $appointmentId,
            'store_id' => 1,
            'customer_id' => $this->seedCustomer(),
            'reservation_id' => null,
            'reception_id' => null,
            'reception_time' => null,
            'arrival_time' => null,
            'type' => 'coming',
            'date' => '2026-03-25',
            'start' => '2026-03-25 09:00:00',
            'end' => '2026-03-25 09:30:00',
            'duration' => 30,
            'status' => $status->value,
            'items' => json_encode([101], JSON_THROW_ON_ERROR),
            'items_name' => '项目A',
            'department_id' => 1,
            'doctor_id' => 11,
            'consultant_id' => 12,
            'technician_id' => 13,
            'anaesthesia' => null,
            'room_id' => 1,
            'create_user_id' => 99,
            'remark' => 'seed appointment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $appointmentId;
    }

    private function mockAuthUser(): void
    {
        $user = User::query()->create([
            'id' => 99,
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::shouldReceive('user')->andReturn($user);
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('keyword')->nullable();
            $table->timestamps();
        });

        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->timestamps();
        });

        Schema::create('department', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('item', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedInteger('parentid')->default(0);
            $table->string('tree')->nullable();
            $table->string('keyword')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->unsignedTinyInteger('child')->default(0);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('store_id')->default(1)->index();
            $table->uuid('customer_id')->index();
            $table->uuid('reservation_id')->nullable();
            $table->uuid('reception_id')->nullable()->index();
            $table->timestamp('reception_time')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->string('type', 10)->nullable();
            $table->date('date');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->unsignedInteger('duration');
            $table->unsignedTinyInteger('status');
            $table->string('items')->nullable();
            $table->string('items_name')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->unsignedInteger('doctor_id')->nullable();
            $table->unsignedInteger('consultant_id')->nullable();
            $table->unsignedInteger('technician_id')->nullable();
            $table->string('anaesthesia')->nullable();
            $table->unsignedInteger('room_id')->nullable();
            $table->unsignedInteger('create_user_id');
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        DB::table('department')->insert([
            'id' => 1,
            'name' => '皮肤科',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('room')->insert([
            'id' => 1,
            'name' => '诊室A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            ['id' => 11, 'name' => '医生A', 'email' => 'doctor@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '咨询A', 'email' => 'consultant@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => '技师A', 'email' => 'technician@example.com', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('item')->insert([
            'id' => 101,
            'name' => '项目A',
            'parentid' => 0,
            'tree' => '0-101',
            'keyword' => '项目A',
            'order' => 101,
            'child' => 0,
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

    private function dispatchJsonRequest(string $method, string $uri, array $payload = []): array
    {
        $request = Request::create($uri, $method, $payload, [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
        app()->instance('request', $request);

        $response = app('router')->dispatch($request);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return [$response, $data];
    }
}
