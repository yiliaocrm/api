<?php

namespace Tests\Unit\Services\CustomerLogRemark;

use App\Models\Customer;
use App\Models\CustomerLog;
use App\Models\Reservation;
use App\Services\CustomerLogRemark\CustomerLogRemarkRenderer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerLogRemarkRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('keyword')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_it_renders_a_generic_change_for_unknown_fields(): void
    {
        $log = new CustomerLog([
            'logable_type' => 'App\\Models\\UnknownThing',
            'original' => ['status' => 1],
            'dirty' => ['status' => 2],
        ]);

        $remark = app(CustomerLogRemarkRenderer::class)->render($log);

        $this->assertSame('字段 status 由1变更为2', $remark);
    }

    public function test_it_joins_multiple_changes_with_full_width_semicolon(): void
    {
        $log = new CustomerLog([
            'logable_type' => 'App\\Models\\UnknownThing',
            'original' => ['name' => '张三', 'phone' => '13800000000'],
            'dirty' => ['name' => '李四', 'phone' => '13900000000'],
        ]);

        $remark = app(CustomerLogRemarkRenderer::class)->render($log);

        $this->assertSame('字段 name 由张三变更为李四；字段 phone 由13800000000变更为13900000000', $remark);
    }

    public function test_it_uses_customer_specific_field_labels(): void
    {
        $log = new CustomerLog([
            'logable_type' => Customer::class,
            'original' => ['name' => '张三'],
            'dirty' => ['name' => '李四'],
        ]);

        $remark = app(CustomerLogRemarkRenderer::class)->render($log);

        $this->assertSame('顾客姓名 由张三变更为李四', $remark);
    }

    public function test_it_formats_user_ids_with_employee_names(): void
    {
        DB::table('users')->insert([
            'id' => 10,
            'name' => '王顾问',
            'email' => 'consultant@example.com',
            'password' => 'secret',
            'keyword' => 'wangguwen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = new CustomerLog([
            'logable_type' => Customer::class,
            'original' => ['consultant' => 10],
            'dirty' => ['consultant' => 0],
        ]);

        $remark = app(CustomerLogRemarkRenderer::class)->render($log);

        $this->assertSame('销售顾问 由王顾问清空', $remark);
    }

    public function test_it_formats_enum_values_for_known_log_types(): void
    {
        $log = new CustomerLog([
            'logable_type' => Reservation::class,
            'original' => ['status' => 1],
            'dirty' => ['status' => 2],
        ]);

        $remark = app(CustomerLogRemarkRenderer::class)->render($log);

        $this->assertSame('预约状态 由未上门变更为已到院', $remark);
    }

    public function test_it_returns_empty_string_when_original_and_dirty_are_both_empty(): void
    {
        $log = new CustomerLog([
            'logable_type' => Customer::class,
            'original' => [],
            'dirty' => [],
        ]);

        $remark = app(CustomerLogRemarkRenderer::class)->render($log);

        $this->assertSame('', $remark);
    }

    public function test_it_returns_empty_string_when_original_and_dirty_are_both_null(): void
    {
        $log = new CustomerLog([
            'logable_type' => Customer::class,
            'original' => null,
            'dirty' => null,
        ]);

        $remark = app(CustomerLogRemarkRenderer::class)->render($log);

        $this->assertSame('', $remark);
    }
}
