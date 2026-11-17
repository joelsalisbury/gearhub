<?php

use App\AvailabilityPeriod;
use App\Models\GearItem;
use App\Models\Organization;
use App\Models\Reservation;
use App\ReservationStatus;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAvailabilityReservationForItem(
    Organization $organization,
    GearItem $gearItem,
    string $startsAt,
    string $endsAt,
    ReservationStatus $status = ReservationStatus::Pending,
    int $quantity = 1,
    ?string $returnedAt = null,
): Reservation {
    $reservation = Reservation::factory()->for($organization)->create([
        'starts_at' => CarbonImmutable::parse($startsAt, 'UTC'),
        'ends_at' => CarbonImmutable::parse($endsAt, 'UTC'),
        'status' => $status,
        'returned_at' => $returnedAt === null ? null : CarbonImmutable::parse($returnedAt, 'UTC'),
    ]);

    $reservation->items()->create([
        'gear_item_id' => $gearItem->id,
        'quantity' => $quantity,
    ]);

    return $reservation;
}

function availabilityServiceTestPeriod(string $startsAt, string $endsAt): AvailabilityPeriod
{
    return new AvailabilityPeriod(
        CarbonImmutable::parse($startsAt, 'UTC'),
        CarbonImmutable::parse($endsAt, 'UTC'),
    );
}

it('blocks an individually tracked item during any overlapping reservation', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create(['name' => 'Trail bike', 'quantity' => 1, 'status' => 'active']);
    $service = new AvailabilityService;

    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 09:00', '2030-01-05 12:00');

    expect($service->availableQuantity(
        $organization,
        $gearItem,
        availabilityServiceTestPeriod('2030-01-05 10:00', '2030-01-05 11:00'),
    ))->toBe(0);
});

it('allows adjacent reservations and periods without overlap', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create(['name' => 'Trail bike', 'quantity' => 1, 'status' => 'active']);
    $service = new AvailabilityService;

    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 09:00', '2030-01-05 12:00');
    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 13:00', '2030-01-05 14:00');

    expect($service->availableQuantity(
        $organization,
        $gearItem,
        availabilityServiceTestPeriod('2030-01-05 12:00', '2030-01-05 13:00'),
    ))->toBe(1);
});

it('ignores reservations wholly before or after the requested period', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create([
        'name' => 'Trail bike',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $service = new AvailabilityService;

    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-04 09:00', '2030-01-04 12:00');
    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-06 09:00', '2030-01-06 12:00');

    expect($service->availableQuantity(
        $organization,
        $gearItem,
        availabilityServiceTestPeriod('2030-01-05 09:00', '2030-01-05 12:00'),
    ))->toBe(1);
});

it('subtracts the maximum concurrent allocation instead of summing every overlap', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create(['name' => 'Helmets', 'quantity' => 10, 'status' => 'active']);
    $service = new AvailabilityService;

    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 09:00', '2030-01-05 12:00', quantity: 4);
    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 10:00', '2030-01-05 11:00', quantity: 3);
    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 11:00', '2030-01-05 13:00', quantity: 2);

    $period = availabilityServiceTestPeriod('2030-01-05 10:00', '2030-01-05 12:00');

    expect($service->availableQuantity($organization, $gearItem, $period))->toBe(3)
        ->and($service->canAccommodate($organization, $gearItem, $period, 3))->toBeTrue()
        ->and($service->canAccommodate($organization, $gearItem, $period, 4))->toBeFalse();
});

it('adds quantities from fully overlapping reservations', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create([
        'name' => 'Helmets',
        'quantity' => 10,
        'status' => 'active',
    ]);

    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 09:00', '2030-01-05 12:00', quantity: 4);
    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 09:00', '2030-01-05 12:00', quantity: 3);

    expect((new AvailabilityService)->availableQuantity(
        $organization,
        $gearItem,
        availabilityServiceTestPeriod('2030-01-05 10:00', '2030-01-05 11:00'),
    ))->toBe(3);
});

it('uses the highest separate allocation when reservations touch the requested period', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create(['name' => 'Helmets', 'quantity' => 10, 'status' => 'active']);
    $service = new AvailabilityService;

    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 09:00', '2030-01-05 10:00', quantity: 4);
    createAvailabilityReservationForItem($organization, $gearItem, '2030-01-05 11:00', '2030-01-05 12:00', quantity: 6);

    expect($service->availableQuantity(
        $organization,
        $gearItem,
        availabilityServiceTestPeriod('2030-01-05 09:00', '2030-01-05 12:00'),
    ))->toBe(4);
});

it('counts only reservation statuses that consume availability', function (
    ReservationStatus $status,
    int $expectedAvailable,
) {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create(['name' => 'Trail bike', 'quantity' => 1, 'status' => 'active']);
    $period = availabilityServiceTestPeriod('2030-01-05 10:00', '2030-01-05 11:00');

    createAvailabilityReservationForItem(
        $organization,
        $gearItem,
        '2030-01-05 09:00',
        '2030-01-05 12:00',
        status: $status,
    );

    expect((new AvailabilityService)->availableQuantity($organization, $gearItem, $period))
        ->toBe($expectedAvailable);
})->with([
    'pending reservations consume capacity' => [ReservationStatus::Pending, 0],
    'confirmed reservations consume capacity' => [ReservationStatus::Confirmed, 0],
    'completed reservations release capacity' => [ReservationStatus::Completed, 1],
    'cancelled reservations release capacity' => [ReservationStatus::Cancelled, 1],
    'declined reservations release capacity' => [ReservationStatus::Declined, 1],
]);

it('keeps checked-out gear unavailable until its actual return', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create(['name' => 'Trail bike', 'quantity' => 1, 'status' => 'active']);
    $service = new AvailabilityService;

    createAvailabilityReservationForItem(
        $organization,
        $gearItem,
        '2030-01-05 09:00',
        '2030-01-05 10:00',
        status: ReservationStatus::CheckedOut,
        returnedAt: '2030-01-05 12:00',
    );

    expect($service->availableQuantity(
        $organization,
        $gearItem,
        availabilityServiceTestPeriod('2030-01-05 10:30', '2030-01-05 11:30'),
    ))->toBe(0)
        ->and($service->availableQuantity(
            $organization,
            $gearItem,
            availabilityServiceTestPeriod('2030-01-05 12:00', '2030-01-05 13:00'),
        ))->toBe(1);
});

it('keeps checked-out gear unavailable when no actual return time has been recorded', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create([
        'name' => 'Trail bike',
        'quantity' => 1,
        'status' => 'active',
    ]);

    createAvailabilityReservationForItem(
        $organization,
        $gearItem,
        '2030-01-05 09:00',
        '2030-01-05 10:00',
        status: ReservationStatus::CheckedOut,
    );

    expect((new AvailabilityService)->availableQuantity(
        $organization,
        $gearItem,
        availabilityServiceTestPeriod('2030-01-06 10:00', '2030-01-06 11:00'),
    ))->toBe(0);
});

it('returns no capacity for unavailable, inactive, or foreign-organization gear', function () {
    $organization = Organization::factory()->create();
    $inactiveOrganization = Organization::factory()->create(['is_active' => false]);
    $otherOrganization = Organization::factory()->create();
    $unavailable = $organization->gearItems()->create([
        'name' => 'Unavailable bike',
        'quantity' => 1,
        'status' => 'unavailable',
    ]);
    $inactiveGear = $inactiveOrganization->gearItems()->create([
        'name' => 'Inactive gear',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $foreignGear = $otherOrganization->gearItems()->create([
        'name' => 'Foreign gear',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $period = availabilityServiceTestPeriod('2030-01-05 10:00', '2030-01-05 11:00');
    $service = new AvailabilityService;

    expect($service->availableQuantities($organization, [$unavailable, $inactiveGear, $foreignGear], $period))
        ->toBe([
            $unavailable->id => 0,
            $inactiveGear->id => 0,
            $foreignGear->id => 0,
        ]);
});

it('does not let reservations owned by another organization reduce capacity', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create(['name' => 'Trail bike', 'quantity' => 1, 'status' => 'active']);
    $service = new AvailabilityService;

    createAvailabilityReservationForItem(
        $otherOrganization,
        $gearItem,
        '2030-01-05 09:00',
        '2030-01-05 12:00',
    );

    expect($service->availableQuantity(
        $organization,
        $gearItem,
        availabilityServiceTestPeriod('2030-01-05 10:00', '2030-01-05 11:00'),
    ))->toBe(1);
});

it('never reports negative availability and rejects nonpositive requested quantities', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create([
        'name' => 'Zero quantity',
        'quantity' => 0,
        'status' => 'active',
    ]);
    $period = availabilityServiceTestPeriod('2030-01-05 10:00', '2030-01-05 11:00');
    $service = new AvailabilityService;

    expect($service->availableQuantity($organization, $gearItem, $period))->toBe(0)
        ->and($service->canAccommodate($organization, $gearItem, $period, 0))->toBeFalse()
        ->and($service->canAccommodate($organization, $gearItem, $period, -1))->toBeFalse();
});

it('interprets local organization times in the organization timezone and returns UTC instants', function () {
    $this->travelTo('2026-02-01 12:00:00 UTC');
    $service = new AvailabilityService;
    $period = $service->periodFromLocalInputs([
        'pickup_date' => '2026-07-10',
        'pickup_time' => '09:30',
        'return_date' => '2026-07-10',
        'return_time' => '12:00',
    ], 'America/Denver');

    expect($period)->toBeInstanceOf(AvailabilityPeriod::class)
        ->and($period->startsAt->format('Y-m-d H:i'))->toBe('2026-07-10 15:30')
        ->and($period->endsAt->format('Y-m-d H:i'))->toBe('2026-07-10 18:00')
        ->and($period->localInputs('America/Denver'))->toBe([
            'pickup_date' => '2026-07-10',
            'pickup_time' => '09:30',
            'return_date' => '2026-07-10',
            'return_time' => '12:00',
        ]);
});
