<?php

namespace Tests\Feature\Consultant;

use App\Http\Requests\Consultant\CreateRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ConsultantCreateRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        $this->seedBaseData();
        $this->mockAuthUser(101);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('item');
        Schema::dropIfExists('medium');
        Schema::dropIfExists('department');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('parameters');

        parent::tearDown();
    }

    public function test_rules_allow_consultant_create_without_reception_parameter(): void
    {
        DB::table('customer')->insert([
            'id' => 1,
            'consultant' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = CreateRequest::create('/consultant/create', 'POST', $this->validPayload(1));
        app()->instance('request', $request);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue(
            $validator->passes(),
            json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
    }

    public function test_rules_still_reject_customer_owned_by_other_consultant_when_only_self_create_enabled(): void
    {
        DB::table('parameters')->where('name', 'consultant_only_self_create')->update([
            'value' => 'true',
        ]);

        DB::table('customer')->insert([
            'id' => 2,
            'consultant' => 202,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = CreateRequest::create('/consultant/create', 'POST', $this->validPayload(2));
        app()->instance('request', $request);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame(
            '系统开启{首诊制}现场咨询不匹配无法分诊',
            $validator->errors()->first('customer_id')
        );
    }

    private function validPayload(int $customerId): array
    {
        return [
            'customer_id' => $customerId,
            'form' => [
                'department_id' => 1,
                'doctor' => null,
                'type' => 1,
                'ek_user' => null,
                'medium_id' => 2,
                'failure_id' => null,
                'items' => [1],
                'remark' => '测试现场咨询',
            ],
        ];
    }

    private function mockAuthUser(int $id): void
    {
        $user = new User;
        $user->id = $id;
        $user->name = '咨询师';

        Auth::shouldReceive('user')->zeroOrMoreTimes()->andReturn($user);
    }

    private function createTables(): void
    {
        Schema::create('parameters', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('consultant')->default(0);
            $table->timestamps();
        });

        Schema::create('department', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('medium', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('item', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name')->nullable();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('parameters')->insert([
            [
                'name' => 'consultant_only_self_create',
                'value' => 'false',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'consultant_allow_multiple_item',
                'value' => 'false',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('department')->insert(['id' => 1, 'name' => '咨询科室']);
        DB::table('medium')->insert(['id' => 2, 'name' => '测试媒介']);
        DB::table('item')->insert(['id' => 1, 'name' => '测试项目']);
    }
}
