<?php

namespace Database\Factories;

use App\Models\GearItem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GearItem>
 */
class GearItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'category' => fake()->word(),
            'identifier' => fake()->unique()->bothify('GEAR-####'),
            'quantity' => 1,
            'image_path' => null,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
