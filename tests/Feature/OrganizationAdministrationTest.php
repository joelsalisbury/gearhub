<?php

use App\Models\Organization;
use App\Models\User;
use App\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function platformAdmin(): User
{
    $user = User::factory()->create();
    $user->forceFill(['is_platform_admin' => true])->save();

    return $user;
}

it('restricts platform organization management to platform administrators', function () {
    $this->get(route('platform.organizations.index'))->assertRedirectToRoute('login');

    $this->actingAs(User::factory()->create())
        ->get(route('platform.organizations.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->post(route('platform.organizations.store'), [
            'name' => 'Unauthorized Organization',
            'slug' => 'unauthorized',
            'timezone' => 'UTC',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('organizations', ['slug' => 'unauthorized']);

    $this->actingAs(platformAdmin())
        ->get(route('platform.organizations.index'))
        ->assertOk()
        ->assertSee('Organizations');
});

it('searches platform organizations by name', function () {
    Organization::factory()->create(['name' => 'Town Tool Library']);
    Organization::factory()->create(['name' => 'Community Camera Club']);

    $this->actingAs(platformAdmin())
        ->get(route('platform.organizations.index', ['q' => 'Camera']))
        ->assertOk()
        ->assertSee('Community Camera Club')
        ->assertDontSee('Town Tool Library');
});

it('lets platform administrators create and edit organizations and their parent relationship', function () {
    $admin = platformAdmin();
    $parent = Organization::factory()->create(['name' => 'Regional Association']);

    $this->actingAs($admin)
        ->post(route('platform.organizations.store'), [
            'name' => 'Community Tool Library',
            'slug' => 'community-tools',
            'description' => 'Tools for local projects.',
            'timezone' => 'America/Chicago',
            'parent_id' => $parent->id,
        ])
        ->assertRedirect();

    $organization = Organization::where('slug', 'community-tools')->firstOrFail();
    expect($organization->parent->is($parent))->toBeTrue();
    $staff = User::factory()->create();
    $organization->users()->attach($staff, ['role' => OrganizationRole::Manager->value]);

    $this->put(route('platform.organizations.update', $organization), [
        'name' => 'Neighborhood Tool Library',
        'slug' => 'neighborhood-tools',
        'description' => 'Tools for neighbors.',
        'timezone' => 'America/Denver',
        'parent_id' => '',
    ])
        ->assertRedirect(route('platform.organizations.show', $organization))
        ->assertSessionHas('status', 'Organization updated. Existing links using the previous slug may no longer work.');

    $organization->refresh();
    expect($organization->name)->toBe('Neighborhood Tool Library')
        ->and($organization->slug)->toBe('neighborhood-tools')
        ->and($organization->parent_id)->toBeNull()
        ->and($organization->users()->whereKey($staff->id)->firstOrFail()->pivot->role)->toBe('manager');
});

it('rejects duplicate slugs and circular parent relationships', function () {
    $admin = platformAdmin();
    $first = Organization::factory()->create(['slug' => 'first']);
    $second = Organization::factory()->create(['slug' => 'second', 'parent_id' => $first->id]);

    $this->actingAs($admin)
        ->put(route('platform.organizations.update', $first), [
            'name' => $first->name,
            'slug' => 'second',
            'description' => '',
            'timezone' => 'UTC',
            'parent_id' => '',
        ])
        ->assertSessionHasErrors('slug');

    $this->actingAs($admin)
        ->put(route('platform.organizations.update', $first), [
            'name' => $first->name,
            'slug' => 'first',
            'description' => '',
            'timezone' => 'UTC',
            'parent_id' => $second->id,
        ])
        ->assertSessionHasErrors('parent_id');
});

it('deactivates and reactivates organizations without deleting their memberships or data', function () {
    $admin = platformAdmin();
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $member->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($admin)
        ->patch(route('platform.organizations.status', $organization))
        ->assertRedirect(route('platform.organizations.show', $organization));

    $organization->refresh();
    expect($organization->isActive())->toBeFalse()
        ->and($organization->users()->whereKey($member->id)->exists())->toBeTrue();

    $this->get(route('organizations.show', ['organization' => $organization->slug]))->assertNotFound();
    $this->get(route('home', ['q' => $organization->name]))
        ->assertOk()
        ->assertDontSee(route('organizations.show', ['organization' => $organization->slug]));
    $this->actingAs($member)
        ->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertOk();

    $this->patch(route('platform.organizations.status', $organization))
        ->assertRedirect(route('platform.organizations.show', $organization));

    $this->get(route('organizations.show', ['organization' => $organization->slug]))->assertOk();
    $this->actingAs($member)
        ->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertOk();
});

it('allows organization admins to update settings but not the slug or parent', function () {
    $organization = Organization::factory()->create([
        'name' => 'Original Name',
        'slug' => 'original',
    ]);
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->patch(route('organizations.admin.settings.update', ['organization' => $organization->slug]), [
            'name' => 'Updated Name',
            'description' => 'A useful description.',
            'timezone' => 'America/New_York',
            'slug' => 'changed',
            'parent_id' => 999,
        ])
        ->assertRedirect();

    $organization->refresh();
    expect($organization->name)->toBe('Updated Name')
        ->and($organization->slug)->toBe('original')
        ->and($organization->parent_id)->toBeNull();
});

it('does not allow organization managers to manage settings', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->create();
    $manager->organizations()->attach($organization, ['role' => OrganizationRole::Manager->value]);

    $this->actingAs($manager)
        ->get(route('organizations.admin.settings.edit', ['organization' => $organization->slug]))
        ->assertForbidden();
});

it('shows organization staff in platform administration', function () {
    $organization = Organization::factory()->create();
    $staff = User::factory()->create(['name' => 'Staff Member']);
    $organization->users()->attach($staff, ['role' => OrganizationRole::Manager->value]);

    $this->actingAs(platformAdmin())
        ->get(route('platform.organizations.show', $organization))
        ->assertOk()
        ->assertSee('Staff Member')
        ->assertSee($staff->email)
        ->assertSee('Manager');
});
