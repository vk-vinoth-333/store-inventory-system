<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'unique_code' => strtoupper(fake()->unique()->bothify('??###')),
            'price_per_unit' => fake()->randomFloat(2, 10, 200),
            'tax_percentage' => fake()->randomElement([5, 8, 10, 12, 18]),
            'stock_on_hand' => fake()->numberBetween(0, 100),
        ];
    }
}
