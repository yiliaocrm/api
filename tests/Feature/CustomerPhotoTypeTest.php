<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
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
        Schema::dropIfExists('customer_photos');
        Schema::dropIfExists('customer_photo_types');

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

    private function createTables(): void
    {
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
    }
}
