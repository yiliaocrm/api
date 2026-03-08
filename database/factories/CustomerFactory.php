<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => fake()->name(),
            'sex' => fake()->randomElement([1, 2]), // 1=male, 2=female
            'birthday' => fake()->date(),
            'idcard' => fake()->numerify('####################'),
            'file_number' => fake()->numerify('F######'),
            'customer_group_id' => null,
            'status' => 1,
            'user_id' => null,
            'ascription' => null,
            'consultant' => null,
            'service_id' => null,
            'doctor_id' => null,
            'balance' => fake()->randomFloat(2, 0, 10000),
            'amount' => fake()->randomFloat(2, 0, 50000),
            'integral' => fake()->randomFloat(2, 0, 1000),
            'total_payment' => fake()->randomFloat(2, 0, 100000),
            'arrearage' => fake()->randomFloat(2, 0, 5000),
            'medium_id' => null,
            'job_id' => null,
            'economic_id' => null,
            'keyword' => '',
        ];
    }
}
