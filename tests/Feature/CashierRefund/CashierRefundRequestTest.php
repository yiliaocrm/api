<?php

namespace Tests\Feature\CashierRefund;

use App\Enums\CashierRefundStatus;
use App\Http\Controllers\Web\CashierRefundController;
use App\Http\Requests\Web\CashierRefundRequest;
use App\Models\CashierRefund;
use App\Models\User;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierRefundRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertApplicationRouteExists(
            'cashier-refund/create',
            'POST',
            'App\Http\Controllers\Web\CashierRefundController@create'
        );
        $this->assertApplicationRouteExists(
            'cashier-refund/remove',
            'GET',
            'App\Http\Controllers\Web\CashierRefundController@remove'
        );
        $this->createTables();
    }

    protected function tearDown(): void
    {
        auth()->logout();

        Schema::dropIfExists('customer_product');
        Schema::dropIfExists('customer');
        Schema::dropIfExists('cashier_refund');

        parent::tearDown();
    }

    public function test_create_method_uses_web_cashier_refund_request(): void
    {
        $method = new \ReflectionMethod(CashierRefundController::class, 'create');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame(CashierRefundRequest::class, $parameters[0]->getType()?->getName());
    }

    public function test_create_rules_require_customer_id_on_web_cashier_refund_request(): void
    {
        $validator = $this->makeCreateValidator([
            'detail' => [
                [
                    'amount' => 100,
                    'times' => 1,
                    'department_id' => 1,
                    'salesman' => [],
                ],
            ],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
    }

    public function test_create_form_data_is_available_on_web_cashier_refund_request(): void
    {
        $this->actingAsRefundUser(99);

        $request = $this->makeCreateRequest([
            'customer_id' => 'customer-1',
            'detail' => [
                [
                    'amount' => 100,
                    'times' => 1,
                    'department_id' => 1,
                    'salesman' => [['id' => 1, 'name' => '销售甲']],
                ],
                [
                    'amount' => 30,
                    'times' => 2,
                    'department_id' => 2,
                    'salesman' => [['id' => 2, 'name' => '销售乙']],
                ],
            ],
        ]);

        $actual = is_callable([$request, 'formData']) ? $request->formData() : null;

        $this->assertSame([
            'customer_id' => 'customer-1',
            'amount' => 130,
            'remark' => null,
            'user_id' => 99,
            'status' => 2,
            'detail' => [
                [
                    'amount' => 100,
                    'times' => 1,
                    'department_id' => 1,
                    'salesman' => [['id' => 1, 'name' => '销售甲']],
                ],
                [
                    'amount' => 30,
                    'times' => 2,
                    'department_id' => 2,
                    'salesman' => [['id' => 2, 'name' => '销售乙']],
                ],
            ],
        ], $actual);
    }

    public function test_create_detail_data_is_available_on_web_cashier_refund_request(): void
    {
        $this->actingAsRefundUser(99);

        $request = $this->makeCreateRequest([
            'customer_id' => 'customer-1',
            'detail' => [
                [
                    'customer_product_id' => 'cp-1',
                    'customer_goods_id' => 'cg-1',
                    'package_id' => 8,
                    'package_name' => '组合套餐',
                    'product_id' => 1,
                    'product_name' => '预收费用',
                    'goods_id' => 6,
                    'goods_name' => '护理耗材',
                    'times' => 2,
                    'unit_id' => 5,
                    'specs' => '10ml',
                    'department_id' => 3,
                    'amount' => 88.5,
                    'salesman' => [['id' => 1, 'name' => '销售甲']],
                    'remark' => '原路退回',
                ],
            ],
        ]);

        $refund = new CashierRefund([
            'id' => 'refund-1',
            'status' => 2,
            'customer_id' => 'customer-1',
        ]);

        $actual = is_callable([$request, 'detailData']) ? $request->detailData($refund) : null;

        $this->assertSame([
            [
                'status' => CashierRefundStatus::PENDING_CHARGE,
                'cashier_refund_id' => 'refund-1',
                'customer_id' => 'customer-1',
                'cashier_id' => null,
                'customer_product_id' => 'cp-1',
                'customer_goods_id' => 'cg-1',
                'package_id' => 8,
                'package_name' => '组合套餐',
                'product_id' => 1,
                'product_name' => '预收费用',
                'goods_id' => 6,
                'goods_name' => '护理耗材',
                'times' => 2,
                'unit_id' => 5,
                'specs' => '10ml',
                'department_id' => 3,
                'amount' => -88.5,
                'salesman' => [['id' => 1, 'name' => '销售甲']],
                'user_id' => 99,
                'cashier_user_id' => null,
                'remark' => '原路退回',
            ],
        ], $actual);
    }

    public function test_remove_rules_require_id_on_web_cashier_refund_request(): void
    {
        $validator = $this->makeRemoveValidator([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('id', $validator->errors()->toArray());
    }

    public function test_remove_rules_reject_non_removed_status_on_web_cashier_refund_request(): void
    {
        DB::table('cashier_refund')->insert([
            'id' => 'refund-1',
            'customer_id' => 'customer-1',
            'cashier_id' => null,
            'amount' => 100.0000,
            'remark' => null,
            'detail' => json_encode([], JSON_THROW_ON_ERROR),
            'status' => 2,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $validator = $this->makeRemoveValidator([
            'id' => 'refund-1',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('不是[退单]状态,无法删除', $validator->errors()->first('id'));
    }

    private function makeRemoveValidator(array $payload)
    {
        $request = CashierRefundRequest::create('/cashier-refund/remove', 'GET', $payload);
        $request->setRouteResolver(fn () => Route::getRoutes()->match(Request::create('/cashier-refund/remove', 'GET')));
        app()->instance('request', $request);

        return validator()->make($request->all(), $request->rules(), $request->messages());
    }

    private function makeCreateValidator(array $payload)
    {
        $request = $this->makeCreateRequest($payload);

        return validator()->make($request->all(), $request->rules(), $request->messages());
    }

    private function makeCreateRequest(array $payload): CashierRefundRequest
    {
        $request = CashierRefundRequest::create('/cashier-refund/create', 'POST', $payload);
        $request->setRouteResolver(fn () => Route::getRoutes()->match(Request::create('/cashier-refund/create', 'POST')));
        app()->instance('request', $request);

        return $request;
    }

    private function createTables(): void
    {
        Schema::create('customer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->decimal('balance', 14, 4)->default(0);
        });

        Schema::create('customer_product', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->decimal('income', 14, 4)->default(0);
        });

        Schema::create('cashier_refund', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->uuid('cashier_id')->nullable();
            $table->decimal('amount', 14, 4);
            $table->text('remark')->nullable();
            $table->text('detail');
            $table->tinyInteger('status');
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    private function actingAsRefundUser(int $id): void
    {
        auth()->setUser(new class($id) extends User implements \Illuminate\Contracts\Auth\Authenticatable
        {
            use Authenticatable;

            public function __construct(int $id)
            {
                $this->id = $id;
            }
        });
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
