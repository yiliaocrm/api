<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerPhotoTypeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_photo_details');
        Schema::dropIfExists('customer_log');
        Schema::dropIfExists('customer_photos');
        Schema::dropIfExists('customer_photo_types');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_create_customer_photo_type(): void
    {
        $response = $this->postJson('/customer-photo-type/create', [
            'name' => '术前',
            'remark' => '系统自带',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $this->assertDatabaseHas('customer_photo_types', ['name' => '术前']);
    }

    public function test_create_duplicate_name_fails(): void
    {
        DB::table('customer_photo_types')->insert([
            'id' => 1,
            'name' => '术前',
            'remark' => '系统自带',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/customer-photo-type/create', [
            'name' => '术前',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
    }

    public function test_info_customer_photo_type(): void
    {
        DB::table('customer_photo_types')->insert([
            'id' => 1,
            'name' => '术前',
            'remark' => '系统自带',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/customer-photo-type/info?id=1');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.name', '术前');
    }

    public function test_update_customer_photo_type(): void
    {
        DB::table('customer_photo_types')->insert([
            'id' => 1,
            'name' => '术前',
            'remark' => '系统自带',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/customer-photo-type/update', [
            'id' => 1,
            'name' => '术前照片',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $this->assertDatabaseHas('customer_photo_types', ['id' => 1, 'name' => '术前照片']);
    }

    public function test_remove_customer_photo_type(): void
    {
        DB::table('customer_photo_types')->insert([
            'id' => 1,
            'name' => '术前',
            'remark' => '系统自带',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/customer-photo-type/remove?id=1');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $this->assertDatabaseMissing('customer_photo_types', ['id' => 1]);
    }

    public function test_remove_referenced_type_fails(): void
    {
        DB::table('customer_photo_types')->insert([
            'id' => 1,
            'name' => '术前',
            'remark' => '系统自带',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_photos')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'customer_id' => \Illuminate\Support\Str::uuid()->toString(),
            'photo_type_id' => 1,
            'title' => '测试相册',
            'remark' => null,
            'create_user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/customer-photo-type/remove?id=1');

        $response->assertOk();
        $response->assertJsonPath('code', 400);
    }

    public function test_index_lists_all_types(): void
    {
        DB::table('customer_photo_types')->insert([
            ['id' => 1, 'name' => '术前', 'remark' => '系统自带', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '术后', 'remark' => '系统自带', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->postJson('/customer-photo-type/index');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_cache_customer_photo_types(): void
    {
        DB::table('customer_photo_types')->insert([
            ['id' => 1, 'name' => '术前', 'remark' => '系统自带', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '术后', 'remark' => '系统自带', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => '恢复', 'remark' => '系统自带', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson('/cache/customer-photo-type');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.name', '术前');
        $response->assertJsonPath('data.1.name', '术后');
        $response->assertJsonPath('data.2.name', '恢复');
    }

    public function test_create_album_returns_photo_type(): void
    {
        $user = $this->createUser();

        $customerId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('customer')->insert([
            'id' => $customerId,
            'name' => '测试顾客',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_photo_types')->insert([
            'id' => 1, 'name' => '术前', 'remark' => '系统自带', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->postJson('/customer-photo/create', [
            'customer_id' => $customerId,
            'title' => '测试相册',
            'photo_type_id' => 1,
            'remark' => '测试备注',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.photo_type.id', 1);
        $response->assertJsonPath('data.photo_type.name', '术前');
    }

    public function test_update_album_returns_photo_type(): void
    {
        $user = $this->createUser();

        $customerId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('customer')->insert([
            'id' => $customerId,
            'name' => '测试顾客',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_photo_types')->insert([
            ['id' => 1, 'name' => '术前', 'remark' => '系统自带', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '术后', 'remark' => '系统自带', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $albumId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('customer_photos')->insert([
            'id' => $albumId,
            'customer_id' => $customerId,
            'photo_type_id' => 1,
            'title' => '测试相册',
            'remark' => null,
            'create_user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/customer-photo/update', [
            'id' => $albumId,
            'title' => '更新后的相册',
            'photo_type_id' => 2,
            'remark' => '更新备注',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.photo_type.id', 2);
        $response->assertJsonPath('data.photo_type.name', '术后');
    }

    private function createUser(): User
    {
        $user = User::query()->create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => 'secret',
        ]);
        Auth::shouldReceive('user')->andReturn($user);

        return $user;
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
            $table->timestamps();
        });

        Schema::create('customer_photo_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('类型名称');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
        });

        Schema::create('customer_photos', function (Blueprint $table) {
            $table->uuid('id')->primary('id');
            $table->uuid('customer_id')->comment('顾客id');
            $table->unsignedInteger('photo_type_id')->comment('照片类型ID');
            $table->string('title')->comment('相册名称');
            $table->text('remark')->nullable()->comment('相册备注');
            $table->unsignedInteger('create_user_id')->comment('创建人');
            $table->timestamps();
        });

        Schema::create('customer_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->nullable();
            $table->string('logable_type')->nullable();
            $table->string('logable_id')->nullable();
            $table->string('action')->nullable();
            $table->unsignedBigInteger('user_id')->default(0);
            $table->json('original')->nullable();
            $table->json('dirty')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_photo_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_photo_id')->comment('相册ID');
            $table->uuid('customer_id')->nullable()->comment('顾客ID');
            $table->string('name')->nullable()->comment('文件名');
            $table->string('thumb')->nullable()->comment('缩略图');
            $table->string('file_path')->nullable()->comment('文件路径');
            $table->string('file_mime')->nullable()->comment('文件类型');
            $table->unsignedInteger('create_user_id')->nullable()->comment('创建人');
            $table->timestamps();
        });
    }
}
