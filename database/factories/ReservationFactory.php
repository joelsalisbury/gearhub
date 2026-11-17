<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Reservation;
use App\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay();

        return [
            'organization_id' => Organization::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
            'status' => ReservationStatus::Pending->value,
            'returned_at' => null,
        ];
    }
}
