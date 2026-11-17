<?php

use App\Models\Organization;
use App\Models\Reservation;
use App\ReservationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows public availability without claiming capacity before dates are selected', function () {
    $organization = Organization::factory()->create(['slug' => 'town-bikes']);
    $organization->gearItems()->create([
        'name' => 'Trail bike',
        'quantity' => 1,
        'status' => 'active',
    ]);

    $this->get(route('organizations.gear.index', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertDontSee('Unavailable')
        ->assertDontSee('1 of 1 available');

    $this->assertGuest();
});

it('calculates public catalog availability for its own organization timezone', function () {
    $this->travelTo('2026-02-01 12:00:00 UTC');
    $organization = Organization::factory()->create([
        'name' => 'Town Bike Library',
        'slug' => 'town-bikes',
        'timezone' => 'America/Denver',
    ]);
    $helmet = $organization->gearItems()->create([
        'name' => 'Loaner helmet',
        'quantity' => 10,
        'status' => 'active',
    ]);
    $bike = $organization->gearItems()->create([
        'name' => 'Trail bike',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $otherOrganization = Organization::factory()->create();
    $otherOrganization->gearItems()->create([
        'name' => "Other group's bike",
        'quantity' => 1,
        'status' => 'active',
    ]);

    $helmetReservation = Reservation::factory()->for($organization)->create([
        'starts_at' => CarbonImmutable::parse('2026-04-04 15:00:00 UTC'),
        'ends_at' => CarbonImmutable::parse('2026-04-04 18:00:00 UTC'),
        'status' => ReservationStatus::Confirmed,
    ]);
    $helmetReservation->items()->create(['gear_item_id' => $helmet->id, 'quantity' => 4]);
    $overlappingHelmetReservation = Reservation::factory()->for($organization)->create([
        'starts_at' => CarbonImmutable::parse('2026-04-04 15:30:00 UTC'),
        'ends_at' => CarbonImmutable::parse('2026-04-04 17:00:00 UTC'),
        'status' => ReservationStatus::Pending,
    ]);
    $overlappingHelmetReservation->items()->create(['gear_item_id' => $helmet->id, 'quantity' => 3]);
    $bikeReservation = Reservation::factory()->for($organization)->create([
        'starts_at' => CarbonImmutable::parse('2026-04-04 15:00:00 UTC'),
        'ends_at' => CarbonImmutable::parse('2026-04-04 18:00:00 UTC'),
        'status' => ReservationStatus::Confirmed,
    ]);
    $bikeReservation->items()->create(['gear_item_id' => $bike->id, 'quantity' => 1]);

    $inputs = [
        'pickup_date' => '2026-04-04',
        'pickup_time' => '09:00',
        'return_date' => '2026-04-04',
        'return_time' => '12:00',
    ];
    $catalog = route('organizations.gear.index', ['organization' => $organization->slug] + $inputs);
    $detail = route('organizations.gear.show', [
        'organization' => $organization->slug,
        'gearItem' => $helmet,
    ] + $inputs);

    $this->get($catalog)
        ->assertOk()
        ->assertSee('3 of 10 available')
        ->assertSee('Unavailable')
        ->assertDontSee("Other group's bike")
        ->assertSee($detail)
        ->assertSee('America/Denver');

    $this->get($detail)
        ->assertOk()
        ->assertSee('3 of 10 available.');

    $this->assertGuest();
});

it('preserves selected dates through catalog filtering and item details', function () {
    $this->travelTo('2026-02-01 12:00:00 UTC');
    $organization = Organization::factory()->create(['slug' => 'town-bikes']);
    $bike = $organization->gearItems()->create([
        'name' => 'Trail bike',
        'category' => 'Bikes',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $organization->gearItems()->create([
        'name' => 'Loaner helmet',
        'category' => 'Helmets',
        'quantity' => 2,
        'status' => 'active',
    ]);
    $inputs = [
        'pickup_date' => '2026-04-04',
        'pickup_time' => '09:00',
        'return_date' => '2026-04-04',
        'return_time' => '12:00',
    ];
    $catalog = route('organizations.gear.index', ['organization' => $organization->slug]);

    $this->get($catalog.'?'.http_build_query($inputs + ['category' => 'Bikes']))
        ->assertOk()
        ->assertSee('Trail bike')
        ->assertDontSee('Loaner helmet')
        ->assertSee('name="pickup_date" value="2026-04-04"', false)
        ->assertSee(route('organizations.gear.show', [
            'organization' => $organization->slug,
            'gearItem' => $bike,
        ] + $inputs));

    $this->get(route('organizations.gear.show', [
        'organization' => $organization->slug,
        'gearItem' => $bike,
    ] + $inputs))
        ->assertOk()
        ->assertSee('Available for your selected dates.')
        ->assertSee('name="return_time"', false)
        ->assertSee('value="12:00"', false)
        ->assertSee(route('organizations.gear.index', ['organization' => $organization->slug] + $inputs));
});

it('rejects invalid borrowing ranges and past pickup times', function () {
    $this->travelTo('2026-02-01 12:00:00 UTC');
    $organization = Organization::factory()->create(['slug' => 'town-bikes']);
    $catalog = route('organizations.gear.index', ['organization' => $organization->slug]);

    $this->get($catalog.'?'.http_build_query([
        'pickup_date' => '2026-04-04',
        'pickup_time' => '12:00',
        'return_date' => '2026-04-04',
        'return_time' => '11:00',
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'return_time' => 'The return date and time must be after pickup.',
        ]);

    $this->get($catalog.'?'.http_build_query([
        'pickup_date' => '2026-01-31',
        'pickup_time' => '12:00',
        'return_date' => '2026-02-01',
        'return_time' => '13:00',
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'pickup_date' => 'Pickup must be in the future.',
        ]);
});

it('rejects incomplete availability periods', function () {
    $this->travelTo('2026-02-01 12:00:00 UTC');
    $organization = Organization::factory()->create(['slug' => 'town-bikes']);

    $this->get(route('organizations.gear.index', [
        'organization' => $organization->slug,
        'pickup_date' => '2026-04-04',
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'pickup_time' => 'Enter both a pickup and return date and time.',
        ]);
});

it('rejects nonexistent and ambiguous daylight-saving pickup times', function (
    array $inputs,
    string $message,
) {
    $this->travelTo('2026-02-01 12:00:00 UTC');
    $organization = Organization::factory()->create([
        'slug' => 'new-york-gear',
        'timezone' => 'America/New_York',
    ]);
    $catalog = route('organizations.gear.index', ['organization' => $organization->slug]);

    $this->get($catalog.'?'.http_build_query($inputs))
        ->assertRedirect()
        ->assertSessionHasErrors(['pickup_time' => $message]);
})->with([
    'nonexistent spring-forward time' => [
        [
            'pickup_date' => '2026-03-08',
            'pickup_time' => '02:30',
            'return_date' => '2026-03-08',
            'return_time' => '04:00',
        ],
        'This local time does not exist because of a daylight-saving time change.',
    ],
    'ambiguous fall-back time' => [
        [
            'pickup_date' => '2026-11-01',
            'pickup_time' => '01:30',
            'return_date' => '2026-11-01',
            'return_time' => '02:30',
        ],
        'This local time is ambiguous because of a daylight-saving time change.',
    ],
]);
