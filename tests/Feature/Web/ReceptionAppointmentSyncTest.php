<?php

namespace Tests\Feature\Web;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Events\Web\WorkflowTriggerEvent;
use App\Http\Controllers\Web\WorkbenchController;
use App\Models\Consultant;
use App\Models\Reception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceptionAppointmentSyncTest extends TestCase
{
    private string $originalTablePrefix = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->withoutExceptionHandling();
        Route::get('/workbench/today', [WorkbenchController::class, 'today']);

        $this->originalTablePrefix = DB::connection()->getTablePrefix();
        DB::connection()->setTablePrefix('cy_');
        Carbon::setTestNow('2026-05-11 09:00:00');
        Event::fake([
            WorkflowTriggerEvent::class,
        ]);
        $this->createTables();
        $this->seedReferenceData();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('reception_items');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('reception');
        Schema::dropIfExists('department');
        Schema::dropIfExists('users');
        Schema::dropIfExists('customer_talk');
        Schema::dropIfExists('customer_life_cycle');
        Schema::dropIfExists('customer_log');
        Schema::dropIfExists('customer_items');
        Schema::dropIfExists('reservation');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('item');
        Schema::dropIfExists('customer');
        DB::connection()->setTablePrefix($this->originalTablePrefix);
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_consultant_create_syncs_arrived_appointment_for_today_workbench(): void
    {
        $consultant = Consultant::query()->create([
            'id' => 'consultant-reception-1',
            'customer_id' => 'customer-1',
            'department_id' => 100,
            'items' => [1],
            'type' => 1,
            'status' => 1,
            'consultant' => 20,
            'reception' => 20,
            'user_id' => 20,
            'medium_id' => 2,
            'doctor' => 40,
            'receptioned' => 1,
            'remark' => '咨询备注',
            'created_at' => '2026-05-11 10:00:00',
            'updated_at' => '2026-05-11 10:00:00',
        ]);

        $this->assertDatabaseHas('appointments', [
            'reception_id' => $consultant->id,
            'customer_id' => 'customer-1',
            'date' => '2026-05-11',
            'status' => AppointmentStatus::ARRIVED->value,
            'type' => AppointmentType::COMING->value,
            'consultant_id' => 20,
            'doctor_id' => 40,
            'remark' => '咨询管理自动生成预约记录',
        ]);

        $appointmentId = DB::table('appointments')->where('reception_id', $consultant->id)->value('id');
        $this->assertNotNull($appointmentId);
        $this->assertSame($appointmentId, Consultant::query()->find($consultant->id)->appointment_id);

        $payload = $this->getJson('/workbench/today?date=2026-05-11')->json();

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertSame($consultant->id, $payload['data']['rows'][0]['reception_id']);
    }

    public function test_reception_create_still_syncs_arrived_appointment(): void
    {
        $reception = Reception::query()->create([
            'id' => 'front-reception-1',
            'customer_id' => 'customer-1',
            'department_id' => 100,
            'items' => [1],
            'type' => 1,
            'status' => 1,
            'consultant' => 20,
            'reception' => 30,
            'user_id' => 30,
            'medium_id' => 2,
            'doctor' => 40,
            'receptioned' => 0,
            'remark' => '分诊备注',
            'created_at' => '2026-05-11 10:00:00',
            'updated_at' => '2026-05-11 10:00:00',
        ]);

        $this->assertDatabaseHas('appointments', [
            'reception_id' => $reception->id,
            'customer_id' => 'customer-1',
            'date' => '2026-05-11',
            'status' => AppointmentStatus::ARRIVED->value,
            'type' => AppointmentType::COMING->value,
            'consultant_id' => 20,
            'doctor_id' => 40,
            'create_user_id' => 30,
            'remark' => '前台分诊自动生成预约记录',
        ]);
    }

    public function test_existing_appointment_is_updated_without_creating_duplicate(): void
    {
        DB::table('appointments')->insert([
            'id' => 'appointment-1',
            'customer_id' => 'customer-1',
            'date' => '2026-05-11',
            'start' => '2026-05-11 09:30:00',
            'end' => '2026-05-11 10:00:00',
            'duration' => 30,
            'status' => AppointmentStatus::PENDING_ARRIVAL->value,
            'type' => AppointmentType::COMING->value,
            'items' => json_encode([1], JSON_THROW_ON_ERROR),
            'items_name' => '咨询项目A',
            'department_id' => 100,
            'doctor_id' => 40,
            'consultant_id' => 20,
            'technician_id' => 0,
            'room_id' => 0,
            'create_user_id' => 30,
            'created_at' => '2026-05-11 09:00:00',
            'updated_at' => '2026-05-11 09:00:00',
        ]);

        Reception::query()->create([
            'id' => 'front-reception-2',
            'appointment_id' => 'appointment-1',
            'customer_id' => 'customer-1',
            'department_id' => 100,
            'items' => [1],
            'type' => 1,
            'status' => 1,
            'consultant' => 20,
            'reception' => 30,
            'user_id' => 30,
            'medium_id' => 2,
            'doctor' => 40,
            'receptioned' => 0,
            'remark' => '分诊备注',
            'created_at' => '2026-05-11 10:00:00',
            'updated_at' => '2026-05-11 10:00:00',
        ]);

        $this->assertSame(1, DB::table('appointments')->count());
        $this->assertDatabaseHas('appointments', [
            'id' => 'appointment-1',
            'reception_id' => 'front-reception-2',
            'status' => AppointmentStatus::ARRIVED->value,
        ]);
        $this->assertNotNull(DB::table('appointments')->where('id', 'appointment-1')->value('arrival_time'));
    }

    private function seedReferenceData(): void
    {
        DB::table('stores')->insert([
            'id' => 1,
            'name' => '默认门店',
            'short_name' => '默认',
            'slot_duration' => 30,
            'created_at' => '2026-05-11 09:00:00',
            'updated_at' => '2026-05-11 09:00:00',
        ]);

        DB::table('customer')->insert([
            'id' => 'customer-1',
            'name' => '客户A',
            'idcard' => 'ID-A',
            'keyword' => '客户A',
            'created_at' => '2026-05-11 09:00:00',
            'updated_at' => '2026-05-11 09:00:00',
        ]);

        DB::table('item')->insert([
            'id' => 1,
            'name' => '咨询项目A',
            'tree' => '1',
        ]);

        DB::table('department')->insert([
            'id' => 100,
            'name' => '咨询科室A',
        ]);

        DB::table('users')->insert([
            ['id' => 20, 'name' => '咨询师A', 'email' => 'consultant@example.com', 'password' => 'secret'],
            ['id' => 30, 'name' => '接待A', 'email' => 'reception@example.com', 'password' => 'secret'],
            ['id' => 40, 'name' => '医生A', 'email' => 'doctor@example.com', 'password' => 'secret'],
        ]);
    }

    private function createTables(): void
    {
        Schema::create('customer', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->string('keyword')->nullable();
            $table->integer('consultant')->nullable();
            $table->timestamp('first_time')->nullable();
            $table->timestamp('last_time')->nullable();
            $table->timestamps();
        });

        Schema::create('item', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->string('tree')->nullable();
        });

        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('short_name');
            $table->time('business_start')->default('09:00:00');
            $table->time('business_end')->default('22:00:00');
            $table->integer('slot_duration')->default(30);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('reservation', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('customer_id')->nullable();
            $table->string('reception_id')->nullable();
            $table->integer('status')->nullable();
            $table->timestamp('cometime')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_items', function (Blueprint $table): void {
            $table->id();
            $table->string('itemable_type');
            $table->string('itemable_id');
            $table->string('customer_id');
            $table->integer('item_id');
            $table->timestamps();
        });

        Schema::create('customer_log', function (Blueprint $table): void {
            $table->id();
            $table->string('logable_type');
            $table->string('logable_id');
            $table->string('customer_id')->nullable();
            $table->json('dirty')->nullable();
            $table->json('original')->nullable();
            $table->string('action')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_life_cycle', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('cycle_type');
            $table->string('cycle_id');
            $table->string('customer_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_talk', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('talk_type');
            $table->string('talk_id');
            $table->string('customer_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('reception', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('customer_id')->index();
            $table->string('appointment_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->json('items')->nullable();
            $table->integer('type')->nullable();
            $table->integer('status')->default(1);
            $table->integer('consultant')->nullable();
            $table->integer('reception')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('ek_user')->nullable();
            $table->integer('doctor')->nullable();
            $table->integer('medium_id')->nullable();
            $table->boolean('receptioned')->default(false);
            $table->integer('failure_id')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedInteger('store_id')->default(1);
            $table->string('customer_id');
            $table->string('reservation_id')->nullable();
            $table->string('reception_id')->nullable();
            $table->timestamp('reception_time')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->string('type', 10)->nullable();
            $table->date('date');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->unsignedInteger('duration');
            $table->unsignedTinyInteger('status');
            $table->json('items')->nullable();
            $table->string('items_name')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->unsignedInteger('doctor_id')->nullable();
            $table->unsignedInteger('consultant_id')->nullable();
            $table->unsignedInteger('technician_id')->nullable();
            $table->unsignedInteger('room_id')->nullable();
            $table->unsignedInteger('create_user_id');
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('reception_items', function (Blueprint $table): void {
            $table->string('reception_id');
            $table->integer('item_id');
        });
    }
}
