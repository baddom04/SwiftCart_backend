<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country'  => $this->faker->country,
            'zip_code' => $this->faker->numerify('####'),
            'city'     => $this->faker->city,
            'street'   => $this->faker->streetAddress,
            'detail'   => $this->faker->secondaryAddress,
        ];
    }
}
