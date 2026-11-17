<?php

use App\Models\Organization;
use App\Models\User;
use App\OrganizationRole;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps the homepage organization-neutral when a pilot organization exists', function () {
    Organization::factory()->create([
        'name' => 'Quiet Corner NEMBA',
        'slug' => 'qcnemba',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Borrow gear from your organization.')
        ->assertSee('Find your organization')
        ->assertDontSee('Quiet Corner NEMBA')
        ->assertDontSee('qcnemba');
});

it('renders the homepage when no organizations exist', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Borrow gear from your organization.')
        ->assertSee('Find your organization');
});

it('shows organization discovery instead of sign in to authenticated visitors', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Find an organization')
        ->assertDontSee('Staff sign in');
});

it('renders different organizations using their own bound context', function () {
    $firstOrganization = Organization::factory()->create([
        'name' => 'Quiet Corner NEMBA',
        'slug' => 'qcnemba',
        'description' => 'Mountain bikes for local riders.',
    ]);
    $secondOrganization = Organization::factory()->create([
        'name' => 'Town Tool Library',
        'slug' => 'town-tools',
        'description' => 'Tools for neighborhood projects.',
    ]);

    $this->get(route('organizations.show', ['organization' => $firstOrganization->slug]))
        ->assertOk()
        ->assertSee('Quiet Corner NEMBA')
        ->assertSee('Mountain bikes for local riders.')
        ->assertDontSee('Town Tool Library');

    $this->get(route('organizations.show', ['organization' => $secondOrganization->slug]))
        ->assertOk()
        ->assertSee('Town Tool Library')
        ->assertSee('Tools for neighborhood projects.')
        ->assertDontSee('Quiet Corner NEMBA');
});

it('returns no matches when there are no organizations', function () {
    $this->get(route('home', ['q' => 'Quiet Corner']))
        ->assertOk()
        ->assertSee('No organizations found.');
});

it('searches organizations by name and links to their public pages', function () {
    $organization = Organization::factory()->create([
        'name' => 'Town Tool Library',
        'slug' => 'town-tools',
        'description' => 'Tools for neighborhood projects.',
    ]);
    Organization::factory()->create([
        'name' => 'Quiet Corner NEMBA',
        'slug' => 'qcnemba',
    ]);

    $this->get(route('home', ['q' => 'Town Tool']))
        ->assertOk()
        ->assertSee('Town Tool Library')
        ->assertSee('Tools for neighborhood projects.')
        ->assertSee(route('organizations.show', ['organization' => $organization->slug]))
        ->assertDontSee('Quiet Corner NEMBA');
});

it('paginates organization search results', function () {
    $organizations = array_map(
        fn (int $number): array => [
            'name' => 'Shared Gear Chapter '.$number,
            'slug' => 'shared-gear-'.$number,
        ],
        range(1, 105),
    );
    Organization::factory()->count(count($organizations))->sequence(...$organizations)->create();

    $this->get(route('home', ['q' => 'Shared Gear']))
        ->assertOk()
        ->assertSee('Page 1 of 11')
        ->assertSee('Next');

    $this->get('/?q=Shared%20Gear&page=2')
        ->assertOk()
        ->assertSee('Page 2 of 11');
});

it('returns not found for an unknown organization slug', function () {
    $this->get('/missing-chapter')->assertNotFound();
});

it('seeds Quiet Corner NEMBA as a child of NEMBA', function () {
    $this->seed(OrganizationSeeder::class);

    $nemba = Organization::where('slug', 'nemba')->firstOrFail();
    $quietCorner = Organization::where('slug', 'qcnemba')->firstOrFail();

    expect($quietCorner->parent->is($nemba))->toBeTrue()
        ->and($quietCorner->timezone)->toBe('America/New_York');
});

it('redirects guests away from an organization workspace', function () {
    $organization = Organization::factory()->create();

    $this->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertRedirectToRoute('login');
});

it('allows organization members to access their own workspace', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee($organization->name)
        ->assertSee('Overview');
});

it('offers only the users organizations in the organization switcher', function () {
    $currentOrganization = Organization::factory()->create(['slug' => 'current']);
    $otherOrganization = Organization::factory()->create(['slug' => 'other']);
    $unrelatedOrganization = Organization::factory()->create(['slug' => 'unrelated']);
    $user = User::factory()->create();
    $user->organizations()->attach($currentOrganization->id, ['role' => OrganizationRole::Admin->value]);
    $user->organizations()->attach($otherOrganization->id, ['role' => OrganizationRole::Manager->value]);

    $this->actingAs($user)
        ->get(route('organizations.admin', ['organization' => $currentOrganization->slug]))
        ->assertOk()
        ->assertSee($otherOrganization->name)
        ->assertSee(route('organizations.admin', ['organization' => $otherOrganization->slug]))
        ->assertDontSee($unrelatedOrganization->name);
});

it('denies a nonmember access to an organization workspace', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertForbidden();
});

it('allows an app admin to access an organization without membership', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->forceFill(['is_app_admin' => true])->save();

    $this->actingAs($user)
        ->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee($otherOrganization->name)
        ->assertSee(route('organizations.admin', ['organization' => $otherOrganization->slug]));

    expect($user->organizations()->exists())->toBeFalse();
});

it('does not allow the app admin flag to be mass assigned', function () {
    $user = User::create([
        'name' => 'Taylor Volunteer',
        'email' => 'taylor@example.test',
        'password' => 'a-long-local-password',
        'is_app_admin' => true,
    ]);

    expect($user->isAppAdmin())->toBeFalse();
});

it('does not grant child access through parent organization membership', function () {
    $parent = Organization::factory()->create();
    $child = Organization::factory()->create(['parent_id' => $parent->id]);
    $user = User::factory()->create();
    $user->organizations()->attach($parent->id, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->get(route('organizations.admin', ['organization' => $child->slug]))
        ->assertForbidden();
});

it('supports admin and manager membership roles', function (OrganizationRole $role) {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => $role->value]);

    $membership = $user->organizations()->whereKey($organization->id)->firstOrFail();

    expect($membership->pivot->role)->toBe($role->value);

    $this->actingAs($user)
        ->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertOk();
})->with([
    'admin' => OrganizationRole::Admin,
    'manager' => OrganizationRole::Manager,
]);
