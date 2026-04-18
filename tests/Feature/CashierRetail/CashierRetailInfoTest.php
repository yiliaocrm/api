<?php

namespace Tests\Feature\CashierRetail;

use App\Http\Controllers\Web\CashierRetailController;
use App\Http\Requests\Web\CashierRetailRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashierRetailInfoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('cashier_pay');
        Schema::dropIfExists('cashier_retail_detail');
        Schema::dropIfExists('cashier_retail');
        Schema::dropIfExists('goods_unit');
        Schema::dropIfExists('goods');
        Schema::dropIfExists('product');
        Schema::dropIfExists('unit');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    public function test_info_returns_goods_units_for_editable_goods_rows(): void
    {
        $customerId = (string) Str::uuid();
        $retailId = (string) Str::uuid();
        $detailId = (string) Str::uuid();

        DB::table('customer')->insert([
            'id' => $customerId,
            'name' => '李四',
            'idcard' => 'KH-001',
            'balance' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('unit')->insert([
            'id' => 3,
            'name' => '盒',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('goods')->insert([
            'id' => 201,
            'name' => '面膜',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('goods_unit')->insert([
            'id' => 1,
            'goods_id' => 201,
            'unit_id' => 3,
            'rate' => 1,
            'retailprice' => 20,
            'basic' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cashier_retail')->insert([
            'id' => $retailId,
            'customer_id' => $customerId,
            'cashier_id' => null,
            'medium_id' => 21,
            'type' => 2,
            'status' => 1,
            'payable' => 20,
            'income' => 0,
            'deposit' => 0,
            'coupon' => 0,
            'arrearage' => 20,
            'remark' => '挂单零售',
            'detail' => null,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cashier_retail_detail')->insert([
            'id' => $detailId,
            'cashier_retail_id' => $retailId,
            'customer_id' => $customerId,
            'type' => 'goods',
            'package_id' => null,
            'package_name' => null,
            'splitable' => null,
            'product_id' => null,
            'product_name' => null,
            'goods_id' => 201,
            'goods_name' => '面膜',
            'times' => 1,
            'unit_id' => 3,
            'specs' => '单片',
            'price' => 20,
            'sales_price' => 20,
            'payable' => 20,
            'amount' => 0,
            'department_id' => 9,
            'salesman' => json_encode([['user_id' => 7, 'rate' => 100]], JSON_UNESCAPED_UNICODE),
            'remark' => null,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = CashierRetailRequest::create('/cashier-retail/info', 'GET', ['id' => $retailId]);
        $response = app(CashierRetailController::class)->info($request);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertSame('KH-001', $payload['data']['customer']['idcard']);
        $this->assertSame(3, $payload['data']['details'][0]['units'][0]['unit_id']);
        $this->assertSame('盒', $payload['data']['details'][0]['units'][0]['unit_name']);
    }

    private function createTables(): void
    {
        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('idcard')->nullable();
            $table->decimal('balance', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('unit', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('product', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('goods', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_unit', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('goods_id')->index();
            $table->integer('unit_id')->index();
            $table->smallInteger('rate')->unsigned();
            $table->decimal('retailprice', 14, 4)->default(0);
            $table->tinyInteger('basic');
            $table->timestamps();
        });

        Schema::create('cashier_retail', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->uuid('cashier_id')->nullable();
            $table->integer('medium_id');
            $table->tinyInteger('type');
            $table->tinyInteger('status');
            $table->decimal('payable', 14, 4)->default(0);
            $table->decimal('income', 14, 4)->default(0);
            $table->decimal('deposit', 14, 4)->default(0);
            $table->decimal('coupon', 14, 4)->default(0);
            $table->decimal('arrearage', 14, 4)->default(0);
            $table->text('remark')->nullable();
            $table->text('detail')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('cashier_retail_detail', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('cashier_retail_id')->index();
            $table->uuid('customer_id')->index();
            $table->string('type', 20);
            $table->integer('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->tinyInteger('splitable')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('goods_id')->nullable();
            $table->string('goods_name')->nullable();
            $table->integer('times');
            $table->integer('unit_id')->nullable();
            $table->string('specs')->nullable();
            $table->decimal('price', 14, 4);
            $table->decimal('sales_price', 14, 4);
            $table->decimal('payable', 14, 4);
            $table->decimal('amount', 14, 4)->default(0);
            $table->integer('department_id');
            $table->text('salesman');
            $table->text('remark')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('cashier_pay', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('cashier_id')->nullable()->index();
            $table->uuid('customer_id')->nullable()->index();
            $table->integer('accounts_id')->nullable();
            $table->decimal('income', 14, 4)->default(0);
            $table->text('remark')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }
}
