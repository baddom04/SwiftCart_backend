<?php

namespace Database\Factories;

use App\Models\Map;
use App\Models\MapSegment;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MapSegment>
 */
class MapSegmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'x'        => $this->faker->numberBetween(0, 100),
            'y'        => $this->faker->numberBetween(0, 100),
            'type'     => $this->faker->randomElement(MapSegment::getSegmentTypes()),
        ];
    }
}
