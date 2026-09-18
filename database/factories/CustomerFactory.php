<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'company_code' => null,
            'status'       => 'Active',
            'phone'        => fake()->phoneNumber(),
            'email'        => fake()->companyEmail(),
        ];
    }
}
