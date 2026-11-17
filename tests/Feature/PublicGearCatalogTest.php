<?php

use App\Models\Organization;
use App\Models\User;
use App\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lists active gear publicly without authentication for any organization', function () {
    $organization = Organization::factory()->create([
        'name' => 'Town Tool Library',
        'slug' => 'town-tools',
    ]);
    $hammer = $organization->gearItems()->create([
        'name' => 'Claw hammer',
        'description' => 'A sturdy hand tool.',
        'category' => 'Hand tools',
        'quantity' => 3,
        'status' => 'active',
    ]);
    $organization->gearItems()->create([
        'name' => 'Unavailable saw',
        'category' => 'Hand tools',
        'status' => 'unavailable',
    ]);

    $this->get(route('organizations.gear.index', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee($hammer->name)
        ->assertSee($hammer->description)
        ->assertSee('Hand tools')
        ->assertSee('Quantity: 3')
        ->assertDontSee('Unavailable saw');

    $this->assertGuest();
});

it('renders a public gear detail page with its photo and organization context', function () {
    Storage::fake('public');
    Storage::disk('public')->put('gear/123/bike.jpg', 'photo');

    $organization = Organization::factory()->create([
        'name' => 'Town Tool Library',
        'slug' => 'town-tools',
    ]);
    $gearItem = $organization->gearItems()->create([
        'name' => 'Trail bike',
        'description' => 'A reliable trail bike.',
        'category' => 'Bikes',
        'quantity' => 2,
        'image_path' => 'gear/123/bike.jpg',
        'status' => 'active',
    ]);

    $this->get(route('organizations.gear.show', [
        'organization' => $organization->slug,
        'gearItem' => $gearItem,
    ]))
        ->assertOk()
        ->assertSee('Trail bike')
        ->assertSee('A reliable trail bike.')
        ->assertSee('Bikes')
        ->assertSeeText('Quantity 2')
        ->assertSee('Town Tool Library')
        ->assertDontSee('Available now')
        ->assertSee(Storage::disk('public')->url('gear/123/bike.jpg'));

    $this->assertGuest();
});

it('never renders internal gear notes or identifiers on public pages', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create([
        'name' => 'Loaner helmet',
        'description' => 'Protective gear for riders.',
        'identifier' => 'INTERNAL-HELMET-004',
        'notes' => 'Keep this in the locked staff room.',
        'quantity' => 1,
        'status' => 'active',
    ]);

    $this->get(route('organizations.gear.index', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee('Loaner helmet')
        ->assertDontSee('INTERNAL-HELMET-004')
        ->assertDontSee('Keep this in the locked staff room.');

    $this->get(route('organizations.gear.show', [
        'organization' => $organization->slug,
        'gearItem' => $gearItem,
    ]))
        ->assertOk()
        ->assertSee('Protective gear for riders.')
        ->assertDontSee('INTERNAL-HELMET-004')
        ->assertDontSee('Keep this in the locked staff room.');
});

it('escapes public gear names and descriptions', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create([
        'name' => '<script>alert(1)</script>',
        'description' => '<img src=x onerror=alert(1)>',
        'quantity' => 1,
        'status' => 'active',
    ]);

    $this->get(route('organizations.gear.show', [
        'organization' => $organization->slug,
        'gearItem' => $gearItem,
    ]))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false);
});

it('filters active gear by name or description and category', function () {
    $organization = Organization::factory()->create(['slug' => 'community-tools']);
    $bike = $organization->gearItems()->create([
        'name' => 'Trail bicycle',
        'description' => 'For rocky paths.',
        'category' => 'Bikes',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $helmet = $organization->gearItems()->create([
        'name' => 'Riding helmet',
        'description' => 'Protective bicycle headwear.',
        'category' => 'Helmets',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $organization->gearItems()->create([
        'name' => 'Spare bicycle pump',
        'category' => 'Accessories',
        'status' => 'unavailable',
    ]);
    $catalog = route('organizations.gear.index', ['organization' => $organization->slug]);

    $this->get($catalog.'?q=rocky&category=Bikes')
        ->assertOk()
        ->assertSee($bike->name)
        ->assertDontSee($helmet->name)
        ->assertDontSee('Spare bicycle pump');

    $this->get($catalog.'?q=Protective&category=Helmets')
        ->assertOk()
        ->assertSee($helmet->name)
        ->assertDontSee($bike->name);
});

it('does not expose gear belonging to another organization or unavailable gear details', function () {
    $firstOrganization = Organization::factory()->create(['slug' => 'first-group']);
    $secondOrganization = Organization::factory()->create(['slug' => 'second-group']);
    $firstGear = $firstOrganization->gearItems()->create([
        'name' => 'First group bike',
        'status' => 'active',
    ]);
    $secondGear = $secondOrganization->gearItems()->create([
        'name' => 'Second group bike',
        'status' => 'active',
    ]);
    $unavailableGear = $firstOrganization->gearItems()->create([
        'name' => 'Unavailable group bike',
        'status' => 'unavailable',
    ]);

    $this->get(route('organizations.gear.index', ['organization' => $firstOrganization->slug]))
        ->assertOk()
        ->assertSee($firstGear->name)
        ->assertDontSee($secondGear->name)
        ->assertDontSee($unavailableGear->name);

    $this->get(route('organizations.gear.show', [
        'organization' => $firstOrganization->slug,
        'gearItem' => $secondGear,
    ]))->assertNotFound();

    $this->get(route('organizations.gear.show', [
        'organization' => $firstOrganization->slug,
        'gearItem' => $unavailableGear,
    ]))->assertNotFound();
});

it('does not expose catalogs or gear details for a deactivated organization', function () {
    $organization = Organization::factory()->create([
        'slug' => 'inactive-group',
        'is_active' => false,
    ]);
    $gearItem = $organization->gearItems()->create([
        'name' => 'Active but hidden bike',
        'status' => 'active',
    ]);

    $this->get(route('organizations.show', ['organization' => $organization->slug]))->assertNotFound();
    $this->get(route('organizations.gear.index', ['organization' => $organization->slug]))->assertNotFound();
    $this->get(route('organizations.gear.show', [
        'organization' => $organization->slug,
        'gearItem' => $gearItem,
    ]))->assertNotFound();
});

it('shows empty states on organizations with no active public gear', function () {
    $organization = Organization::factory()->create(['slug' => 'empty-group']);
    $organization->gearItems()->create([
        'name' => 'Staff-only item',
        'status' => 'unavailable',
    ]);

    $this->get(route('organizations.show', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee('has not listed any public gear yet')
        ->assertSee(route('organizations.gear.index', ['organization' => $organization->slug]));

    $this->get(route('organizations.gear.index', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee('No gear listed yet')
        ->assertDontSee('Staff-only item');
});

it('paginates public gear and keeps filters in the page links', function () {
    $organization = Organization::factory()->create(['slug' => 'gear-club']);

    foreach (range(1, 13) as $number) {
        $organization->gearItems()->create([
            'name' => sprintf('Shared gear %02d', $number),
            'category' => 'General',
            'status' => 'active',
        ]);
    }

    $catalog = route('organizations.gear.index', ['organization' => $organization->slug]);

    $this->get($catalog.'?category=General')
        ->assertOk()
        ->assertSee('Shared gear 01')
        ->assertDontSee('Shared gear 13')
        ->assertSee('Next')
        ->assertSee('category=General');

    $this->get($catalog.'?category=General&page=2')
        ->assertOk()
        ->assertSee('Shared gear 13');
});

it('links the organization page and staff inventory screen to the public catalog', function () {
    $organization = Organization::factory()->create(['slug' => 'camera-club']);

    $this->get(route('organizations.show', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee(route('organizations.gear.index', ['organization' => $organization->slug]));

    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->get(route('organizations.admin.gear.index', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee('View public catalog')
        ->assertSee(route('organizations.gear.index', ['organization' => $organization->slug]));
});
