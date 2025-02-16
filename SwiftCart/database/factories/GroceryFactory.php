<?php

namespace Database\Factories;

use App\Models\Grocery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grocery>
 */
class GroceryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isNullQuantity = rand(1, 4) == 1;

        $quantity = $isNullQuantity ? null : fake()->randomNumber(3, false);
        $unit = $isNullQuantity ? null : Grocery::getUnitTypes()[rand(0, count(Grocery::getUnitTypes()) - 1)];

        return [
            'name' => fake()->word(),
            'quantity' => $quantity,
            'unit' => $unit,
            'description' => fake()->sentence(),
        ];
    }
}
