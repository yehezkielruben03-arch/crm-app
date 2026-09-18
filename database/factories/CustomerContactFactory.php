<?php

namespace Database\Factories;

use App\Models\CustomerContact;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerContactFactory extends Factory
{
    protected $model = CustomerContact::class;

    public function definition(): array
    {
        return [
            'name'    => fake()->name(),
            'position'=> fake()->jobTitle(),
            'phone'   => fake()->phoneNumber(),
            'email'   => fake()->email(),
        ];
    }
}
