<?php

namespace Tests\Feature\CashierInvoice;

use App\Models\User;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierInvoiceActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        auth()->logout();

        Schema::dropIfExists('cashier_invoice_details');
        Schema::dropIfExists('cashier_invoices');
        Schema::dropIfExists('customer_product');
        Schema::dropIfExists('customer_goods');
        Schema::dropIfExists('cashier');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_info_returns_invoice_with_customer_and_details(): void
    {
        $this->seedBaseData();

        DB::table('cashier_invoices')->insert([
            'id' => 10,
            'customer_id' => 'customer-1',
            'type' => 'receipt',
            'key' => 'KP202603010001',
            'date' => '2026-03-01',
            'create_user_id' => 1,
            'amount' => 88.0000,
            'created_at' => '2026-03-01 10:00:00',
            'updated_at' => '2026-03-01 10:00:00',
        ]);

        DB::table('cashier_invoice_details')->insert([
            [
                'cashier_invoice_id' => 10,
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-1',
                'name' => '细胞修复',
                'times' => 2,
                'invoice_amount' => 50,
                'income' => 50,
                'deposit' => 0,
                'created_at' => '2026-03-01 10:00:00',
                'updated_at' => '2026-03-01 10:00:00',
            ],
            [
                'cashier_invoice_id' => 10,
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-2',
                'name' => '耗材补开',
                'times' => 1,
                'invoice_amount' => 38,
                'income' => 38,
                'deposit' => 0,
                'created_at' => '2026-03-01 10:05:00',
                'updated_at' => '2026-03-01 10:05:00',
            ],
        ]);

        $payload = $this->dispatchRequest('/cashier-invoice/info', 'GET', [
            'id' => 10,
        ]);

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame('customer-1', $payload['data']['customer']['id']);
        $this->assertCount(2, $payload['data']['details']);
        $this->assertSame('receipt', $payload['data']['type']);
        $this->assertSame('收据', $payload['data']['type_text'] ?? null);
    }

    public function test_create_creates_invoice_and_details_with_expected_key_and_amount(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        $payload = $this->dispatchRequest('/cashier-invoice/create', 'POST', $this->buildCreatePayload('2026-03-15'));

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $invoice = DB::table('cashier_invoices')->find($payload['data']['id']);
        $this->assertSame('KP202603150001', $invoice->key);
        $this->assertSame(188.25, (float) $invoice->amount);

        $details = DB::table('cashier_invoice_details')
            ->where('cashier_invoice_id', $invoice->id)
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $details);
        $this->assertSame('项目A', $details[0]->name);
        $this->assertSame(3, (int) $details[1]->times);
    }

    public function test_create_uses_invoice_date_for_key_increment_when_backfilling_same_date(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        DB::table('cashier_invoices')->insert([
            'id' => 20,
            'customer_id' => 'customer-1',
            'type' => 'invoice',
            'key' => 'KP202603010001',
            'date' => '2026-03-01',
            'create_user_id' => 1,
            'amount' => 100,
            'created_at' => '2026-04-01 10:00:00',
            'updated_at' => '2026-04-01 10:00:00',
        ]);

        $payload = $this->dispatchRequest('/cashier-invoice/create', 'POST', $this->buildCreatePayload('2026-03-01'));

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $invoice = DB::table('cashier_invoices')->find($payload['data']['id']);
        $this->assertSame('KP202603010002', $invoice->key);
    }

    public function test_update_keeps_original_create_user_id_and_replaces_details(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(99);

        DB::table('cashier_invoices')->insert([
            'id' => 30,
            'customer_id' => 'customer-1',
            'type' => 'receipt',
            'key' => 'KP202603120001',
            'date' => '2026-03-12',
            'create_user_id' => 8,
            'amount' => 100,
            'created_at' => '2026-03-12 10:00:00',
            'updated_at' => '2026-03-12 10:00:00',
        ]);
        DB::table('cashier_invoice_details')->insert([
            [
                'cashier_invoice_id' => 30,
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-1',
                'name' => '旧明细1',
                'times' => 1,
                'invoice_amount' => 40,
                'income' => 40,
                'deposit' => 0,
                'created_at' => '2026-03-12 10:00:00',
                'updated_at' => '2026-03-12 10:00:00',
            ],
            [
                'cashier_invoice_id' => 30,
                'customer_id' => 'customer-1',
                'cashier_id' => 'cashier-2',
                'name' => '旧明细2',
                'times' => 1,
                'invoice_amount' => 60,
                'income' => 60,
                'deposit' => 0,
                'created_at' => '2026-03-12 10:00:00',
                'updated_at' => '2026-03-12 10:00:00',
            ],
        ]);

        $payload = $this->dispatchRequest('/cashier-invoice/update', 'POST', [
            'id' => 30,
            'form' => [
                'date' => '2026-03-12',
                'type' => 'invoice',
                'code' => 'NEW-CODE',
                'number' => 'NEW-NO',
            ],
            'grid' => [
                [
                    'cashier_id' => 'cashier-1',
                    'name' => '新明细',
                    'times' => 5,
                    'invoice_amount' => 200,
                    'income' => 190,
                    'deposit' => 10,
                ],
            ],
        ]);

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $invoice = DB::table('cashier_invoices')->find(30);
        $this->assertSame(8, (int) $invoice->create_user_id);
        $this->assertSame(200.0, (float) $invoice->amount);

        $details = DB::table('cashier_invoice_details')->where('cashier_invoice_id', 30)->get();
        $this->assertCount(1, $details);
        $this->assertSame('新明细', $details[0]->name);
        $this->assertSame(5, (int) $details[0]->times);
    }

    public function test_create_returns_400_when_grid_name_missing(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        $payload = $this->buildCreatePayload('2026-03-21');
        unset($payload['grid'][0]['name']);

        $response = $this->dispatchRequest('/cashier-invoice/create', 'POST', $payload);

        $this->assertNotSame(500, $response['code'] ?? 500, json_encode($response, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $response['code'] ?? null, json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function test_create_returns_400_when_grid_is_empty(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        $payload = $this->buildCreatePayload('2026-03-22');
        $payload['grid'] = [];

        $response = $this->dispatchRequest('/cashier-invoice/create', 'POST', $payload);

        $this->assertNotSame(500, $response['code'] ?? 500, json_encode($response, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $response['code'] ?? null, json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function test_update_returns_400_when_grid_times_missing(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        DB::table('cashier_invoices')->insert([
            'id' => 31,
            'customer_id' => 'customer-1',
            'type' => 'receipt',
            'key' => 'KP202603210001',
            'date' => '2026-03-21',
            'create_user_id' => 9,
            'amount' => 50,
            'created_at' => '2026-03-21 10:00:00',
            'updated_at' => '2026-03-21 10:00:00',
        ]);

        $response = $this->dispatchRequest('/cashier-invoice/update', 'POST', [
            'id' => 31,
            'form' => [
                'date' => '2026-03-21',
                'type' => 'receipt',
            ],
            'grid' => [
                [
                    'cashier_id' => 'cashier-1',
                    'name' => '更新明细',
                    'invoice_amount' => 50,
                    'income' => 50,
                    'deposit' => 0,
                ],
            ],
        ]);

        $this->assertNotSame(500, $response['code'] ?? 500, json_encode($response, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $response['code'] ?? null, json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function test_update_returns_400_when_grid_is_empty(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        DB::table('cashier_invoices')->insert([
            'id' => 32,
            'customer_id' => 'customer-1',
            'type' => 'invoice',
            'key' => 'KP202603220001',
            'date' => '2026-03-22',
            'create_user_id' => 9,
            'amount' => 50,
            'created_at' => '2026-03-22 10:00:00',
            'updated_at' => '2026-03-22 10:00:00',
        ]);

        $response = $this->dispatchRequest('/cashier-invoice/update', 'POST', [
            'id' => 32,
            'form' => [
                'date' => '2026-03-22',
                'type' => 'invoice',
            ],
            'grid' => [],
        ]);

        $this->assertNotSame(500, $response['code'] ?? 500, json_encode($response, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $response['code'] ?? null, json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function test_create_returns_400_when_income_or_deposit_is_not_numeric(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        $payload = $this->buildCreatePayload('2026-03-23');
        $payload['grid'][0]['income'] = 'not-number';
        $payload['grid'][1]['deposit'] = 'also-not-number';

        $response = $this->dispatchRequest('/cashier-invoice/create', 'POST', $payload);

        $this->assertNotSame(500, $response['code'] ?? 500, json_encode($response, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $response['code'] ?? null, json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function test_create_key_generation_handles_non_continuous_same_date_keys(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        DB::table('cashier_invoices')->insert([
            [
                'id' => 40,
                'customer_id' => 'customer-1',
                'type' => 'invoice',
                'key' => 'KP202603250001',
                'date' => '2026-03-25',
                'create_user_id' => 1,
                'amount' => 100,
                'created_at' => '2026-03-25 10:00:00',
                'updated_at' => '2026-03-25 10:00:00',
            ],
            [
                'id' => 41,
                'customer_id' => 'customer-1',
                'type' => 'invoice',
                'key' => 'KP202603250003',
                'date' => '2026-03-25',
                'create_user_id' => 1,
                'amount' => 100,
                'created_at' => '2026-03-25 11:00:00',
                'updated_at' => '2026-03-25 11:00:00',
            ],
        ]);

        $payload = $this->dispatchRequest('/cashier-invoice/create', 'POST', $this->buildCreatePayload('2026-03-25'));

        $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $invoice = DB::table('cashier_invoices')->find($payload['data']['id']);
        $this->assertSame('KP202603250004', $invoice->key);
    }

    public function test_create_ignores_same_date_creation_lock_when_creating(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        $lock = Cache::lock('cashier-invoice:create:2026-03-26', 10);
        $this->assertTrue($lock->get());

        try {
            $payload = $this->dispatchRequest('/cashier-invoice/create', 'POST', $this->buildCreatePayload('2026-03-26'));
            $this->assertSame(200, $payload['code'] ?? null, json_encode($payload, JSON_UNESCAPED_UNICODE));

            $invoice = DB::table('cashier_invoices')->find($payload['data']['id']);
            $this->assertSame('KP202603260001', $invoice->key);
        } finally {
            $lock->release();
        }
    }

    public function test_create_returns_400_when_type_is_not_supported(): void
    {
        $this->seedBaseData();
        $this->mockLoginUser(9);

        $payload = $this->buildCreatePayload('2026-03-27');
        $payload['form']['type'] = 'normal';

        $response = $this->dispatchRequest('/cashier-invoice/create', 'POST', $payload);

        $this->assertNotSame(500, $response['code'] ?? 500, json_encode($response, JSON_UNESCAPED_UNICODE));
        $this->assertSame(400, $response['code'] ?? null, json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function test_customer_product_and_customer_goods_support_post_and_filter_by_customer_id(): void
    {
        $this->seedBaseData();

        DB::table('customer_product')->insert([
            ['id' => 'cp-1', 'customer_id' => 'customer-1', 'product_name' => '项目1', 'times' => 3, 'leftover' => 2, 'invoice_amount' => 10],
            ['id' => 'cp-2', 'customer_id' => 'customer-2', 'product_name' => '项目2', 'times' => 1, 'leftover' => 1, 'invoice_amount' => 20],
        ]);
        DB::table('customer_goods')->insert([
            ['id' => 'cg-1', 'customer_id' => 'customer-1', 'goods_name' => '物品1', 'number' => 5, 'leftover' => 4, 'invoice_amount' => 11],
            ['id' => 'cg-2', 'customer_id' => 'customer-2', 'goods_name' => '物品2', 'number' => 2, 'leftover' => 1, 'invoice_amount' => 22],
        ]);

        $productPayload = $this->dispatchRequest('/cashier-invoice/customer-product', 'POST', [
            'customer_id' => 'customer-1',
        ]);
        $goodsPayload = $this->dispatchRequest('/cashier-invoice/customer-goods', 'POST', [
            'customer_id' => 'customer-1',
        ]);

        $this->assertSame(200, $productPayload['code'] ?? null, json_encode($productPayload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(1, count($productPayload['data']));
        $this->assertSame('customer-1', $productPayload['data'][0]['customer_id']);

        $this->assertSame(200, $goodsPayload['code'] ?? null, json_encode($goodsPayload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(1, count($goodsPayload['data']));
        $this->assertSame('customer-1', $goodsPayload['data'][0]['customer_id']);
    }

    private function buildCreatePayload(string $date): array
    {
        return [
            'customer_id' => 'customer-1',
            'form' => [
                'date' => $date,
                'type' => 'invoice',
                'code' => 'CODE-001',
                'number' => 'NO-001',
                'tax_number' => 'TAX-001',
                'title' => '某某公司',
                'bank_name' => '测试银行',
                'bank_account' => '622200001',
                'remark' => '测试开票',
            ],
            'grid' => [
                [
                    'cashier_id' => 'cashier-1',
                    'name' => '项目A',
                    'times' => 2,
                    'invoice_amount' => 100.25,
                    'income' => 80.25,
                    'deposit' => 20,
                ],
                [
                    'cashier_id' => 'cashier-2',
                    'name' => '项目B',
                    'times' => 3,
                    'invoice_amount' => 88.00,
                    'income' => 88.00,
                    'deposit' => 0,
                ],
            ],
        ];
    }

    private function seedBaseData(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => '创建人1'],
            ['id' => 8, 'name' => '创建人8'],
            ['id' => 9, 'name' => '创建人9'],
            ['id' => 99, 'name' => '创建人99'],
        ]);

        DB::table('customer')->insert([
            ['id' => 'customer-1', 'name' => '顾客甲', 'idcard' => 'ID-1', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'customer-2', 'name' => '顾客乙', 'idcard' => 'ID-2', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('cashier')->insert([
            ['id' => 'cashier-1'],
            ['id' => 'cashier-2'],
        ]);
    }

    private function mockLoginUser(int $id): void
    {
        $user = new class(['id' => $id, 'name' => 'U'.$id]) extends User implements AuthenticatableContract
        {
            use Authenticatable;
        };

        auth()->setUser($user);
    }

    private function dispatchRequest(string $uri, string $method, array $data = []): array
    {
        $request = Request::create(
            $uri,
            $method,
            $data,
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        app()->instance('request', $request);

        try {
            $response = app('router')->dispatch($request);
            $content = $response->getContent();
            $decoded = json_decode($content, true);
        } catch (\Throwable $exception) {
            return [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'http_status' => $response->getStatusCode(),
            'raw' => $content,
        ];
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('idcard')->nullable();
            $table->timestamps();
        });

        Schema::create('cashier', function (Blueprint $table): void {
            $table->uuid('id')->primary();
        });

        Schema::create('cashier_invoices', function (Blueprint $table): void {
            $table->increments('id');
            $table->uuid('customer_id')->index();
            $table->string('type');
            $table->string('key');
            $table->date('date');
            $table->string('code')->nullable();
            $table->string('number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('title')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->integer('create_user_id')->nullable();
            $table->text('remark')->nullable();
            $table->decimal('amount', 14, 4);
            $table->timestamps();
        });

        Schema::create('cashier_invoice_details', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('cashier_invoice_id')->index();
            $table->uuid('customer_id')->index();
            $table->uuid('cashier_id')->index();
            $table->uuid('customer_product_id')->nullable();
            $table->uuid('customer_goods_id')->nullable();
            $table->integer('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('goods_id')->nullable();
            $table->string('goods_name')->nullable();
            $table->string('name');
            $table->unsignedInteger('times');
            $table->unsignedInteger('unit_id')->nullable();
            $table->string('unit_name', 10)->nullable();
            $table->string('specs')->nullable();
            $table->decimal('invoice_amount', 14, 4);
            $table->decimal('income', 14, 4);
            $table->decimal('deposit', 14, 4);
            $table->timestamps();
        });

        Schema::create('customer_product', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->string('product_name')->nullable();
            $table->integer('times')->default(0);
            $table->integer('leftover')->default(0);
            $table->decimal('invoice_amount', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('customer_goods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->string('goods_name')->nullable();
            $table->integer('number')->default(0);
            $table->integer('leftover')->default(0);
            $table->decimal('invoice_amount', 14, 4)->default(0);
            $table->timestamps();
        });
    }
}
