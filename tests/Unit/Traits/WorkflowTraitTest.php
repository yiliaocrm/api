<?php

namespace Tests\Unit\Traits;

use App\Models\Customer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createCustomerTable();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer');
        Schema::dropIfExists('customer_phones');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('followup');
        Schema::dropIfExists('treatment');

        parent::tearDown();
    }

    /**
     * Test that payload returns full model fields when workflowPayloadFields is not defined.
     */
    public function test_payload_returns_full_fields_when_whitelist_is_not_defined(): void
    {
        // Create a customer
        $customer = $this->createCustomer([
            'name' => 'Test Customer',
            'sex' => 1,
            'status' => 1,
        ]);

        // Load relationships to verify they are NOT included in payload
        $customer->load(['medium', 'job', 'economic']);

        // Get payload
        $payload = $customer->getDefaultPayload();

        // Verify specified fields are present
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('sex', $payload);
        $this->assertArrayHasKey('status', $payload);

        // Verify relationships are NOT included
        $this->assertArrayNotHasKey('medium', $payload);
        $this->assertArrayNotHasKey('job', $payload);
        $this->assertArrayNotHasKey('economic', $payload);
        $this->assertArrayNotHasKey('phones', $payload);
        $this->assertArrayNotHasKey('tags', $payload);

        // Verify all visible model fields are present when whitelist is undefined
        $expectedFields = array_values(array_diff(
            Schema::getColumnListing('customer'),
            ['keyword'] // hidden field on Customer model
        ));

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $payload, "Field {$field} should be in payload");
        }

        // Verify payload contains exactly all visible model fields
        $this->assertCount(count($expectedFields), $payload);
    }

    /**
     * Test that payload is lightweight and doesn't include relationship data.
     */
    public function test_payload_is_lightweight_without_relationships(): void
    {
        // Create a customer with relationships
        $customer = $this->createCustomer();

        // Eagerly load multiple relationships
        $customer->load([
            'medium',
            'job',
            'economic',
            'phones',
            'tags',
            'followups',
            'treatments',
        ]);

        // Get payload using the new method
        $payload = $customer->getDefaultPayload();

        // Serialize to check size
        $payloadSize = strlen(json_encode($payload));

        // Payload should be small (less than 5KB for a single customer record)
        $this->assertLessThan(5000, $payloadSize, 'Payload should be lightweight');

        // Verify no relationship keys exist
        $relationshipKeys = [
            'medium', 'job', 'economic', 'phones', 'tags',
            'followups', 'treatments', 'appointments', 'products',
        ];

        foreach ($relationshipKeys as $key) {
            $this->assertArrayNotHasKey($key, $payload, "Relationship {$key} should not be in payload");
        }
    }

    /**
     * Test that the method queries fresh data from database.
     */
    public function test_payload_queries_fresh_data_from_database(): void
    {
        // Create a customer
        $customer = $this->createCustomer(['name' => 'Original Name']);

        // Modify the customer in database directly (bypassing the model instance)
        Customer::where('id', $customer->id)->update(['name' => 'Updated Name']);

        // Get payload - should reflect the updated database value
        $payload = $customer->getDefaultPayload();

        // Verify it has the fresh data from database, not the stale model data
        $this->assertEquals('Updated Name', $payload['name']);
        $this->assertNotEquals('Original Name', $payload['name']);
    }

    /**
     * Test that casts are still applied to payload data.
     */
    public function test_payload_applies_model_casts(): void
    {
        // Create a customer with numeric fields
        $customer = $this->createCustomer([
            'balance' => '100.50',
            'amount' => '200.75',
            'integral' => '50.25',
        ]);

        $payload = $customer->getDefaultPayload();

        // Verify casts are applied (should be float, not string)
        $this->assertIsFloat($payload['balance']);
        $this->assertIsFloat($payload['amount']);
        $this->assertIsFloat($payload['integral']);
        $this->assertEquals(100.50, $payload['balance']);
        $this->assertEquals(200.75, $payload['amount']);
        $this->assertEquals(50.25, $payload['integral']);
    }

    private function createCustomerTable(): void
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idcard')->nullable();
            $table->string('file_number')->nullable();
            $table->string('name');
            $table->string('qq', 20)->nullable();
            $table->string('wechat', 30)->nullable();
            $table->string('sfz', 30)->nullable();
            $table->integer('job_id')->nullable();
            $table->integer('economic_id')->nullable();
            $table->integer('marital')->nullable();
            $table->tinyInteger('sex');
            $table->date('birthday')->nullable();
            $table->integer('age')->nullable();
            $table->integer('address_id');
            $table->integer('level_id')->default(1);
            $table->integer('medium_id')->nullable();
            $table->unsignedInteger('referrer_user_id')->nullable();
            $table->uuid('referrer_customer_id')->nullable();
            $table->integer('department_id')->default(0);
            $table->decimal('total_payment', 14, 4)->default(0);
            $table->decimal('balance', 14, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->decimal('arrearage', 14, 4)->default(0);
            $table->decimal('integral', 14, 4)->default(0);
            $table->decimal('expend_integral', 14, 4)->default(0);
            $table->timestamp('first_time')->nullable();
            $table->timestamp('last_time')->nullable();
            $table->timestamp('last_followup')->nullable();
            $table->timestamp('last_treatment')->nullable();
            $table->integer('ascription')->nullable();
            $table->integer('consultant')->nullable();
            $table->integer('service_id')->nullable();
            $table->integer('doctor_id')->nullable();
            $table->integer('user_id');
            $table->string('keyword');
            $table->text('remark')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('customer_group_id')->nullable();
            $table->timestamps();
            $table->index(['consultant', 'ascription']);
        });

        // customer_phones table
        Schema::create('customer_phones', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id');
            $table->string('phone', 20);
            $table->tinyInteger('is_default')->default(0);
            $table->timestamps();
        });

        // tags table
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // customer_tags pivot table
        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id');
            $table->unsignedBigInteger('tags_id');
            $table->timestamps();
        });

        // followup table (singular, matching Followup model)
        Schema::create('followup', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->text('content')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        // treatment table (singular, matching Treatment model)
        Schema::create('treatment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->string('title');
            $table->timestamps();
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(array $overrides = []): Customer
    {
        return Customer::withoutEvents(fn (): Customer => Customer::factory()->create(array_merge([
            'address_id' => 0,
            'user_id' => 0,
        ], $overrides)));
    }
}
