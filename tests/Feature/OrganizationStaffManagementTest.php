<?php

use App\Models\Organization;
use App\Models\User;
use App\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function organizationAdmin(Organization $organization): User
{
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    return $user;
}

it('allows an organization admin to add, change, and remove staff', function () {
    $organization = Organization::factory()->create();
    $admin = organizationAdmin($organization);
    $staff = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('organizations.admin.settings.staff.store', ['organization' => $organization->slug]), [
            'email' => $staff->email,
            'role' => 'manager',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Staff member added.');

    expect($organization->users()->whereKey($staff->id)->firstOrFail()->pivot->role)->toBe('manager');

    $this->patch(route('organizations.admin.settings.staff.update', [
        'organization' => $organization->slug,
        'user' => $staff->id,
    ]), ['role' => 'admin'])->assertRedirect();

    expect($organization->users()->whereKey($staff->id)->firstOrFail()->pivot->role)->toBe('admin');

    $this->delete(route('organizations.admin.settings.staff.destroy', [
        'organization' => $organization->slug,
        'user' => $staff->id,
    ]))->assertRedirect();

    expect($organization->users()->whereKey($staff->id)->exists())->toBeFalse();
});

it('requires an existing account and valid organization role when adding staff', function () {
    $organization = Organization::factory()->create();
    $admin = organizationAdmin($organization);

    $this->actingAs($admin)
        ->post(route('organizations.admin.settings.staff.store', ['organization' => $organization->slug]), [
            'email' => 'missing@example.test',
            'role' => 'owner',
        ])
        ->assertSessionHasErrors(['email', 'role']);
});

it('prevents demoting or removing the last organization admin', function () {
    $organization = Organization::factory()->create();
    $admin = organizationAdmin($organization);

    $this->actingAs($admin)
        ->patch(route('organizations.admin.settings.staff.update', [
            'organization' => $organization->slug,
            'user' => $admin->id,
        ]), ['role' => 'manager'])
        ->assertSessionHasErrors('staff');

    $this->delete(route('organizations.admin.settings.staff.destroy', [
        'organization' => $organization->slug,
        'user' => $admin->id,
    ]))->assertSessionHasErrors('staff');

    expect($organization->users()->whereKey($admin->id)->firstOrFail()->pivot->role)->toBe('admin');
});

it('does not allow managers to change staff and scopes staff routes to the organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $manager = User::factory()->create();
    $otherStaff = User::factory()->create();
    $manager->organizations()->attach($organization, ['role' => OrganizationRole::Manager->value]);
    $otherOrganization->users()->attach($otherStaff, ['role' => OrganizationRole::Manager->value]);

    $this->actingAs($manager)
        ->post(route('organizations.admin.settings.staff.store', ['organization' => $organization->slug]), [
            'email' => $otherStaff->email,
            'role' => 'manager',
        ])
        ->assertForbidden();

    $admin = organizationAdmin($organization);
    $this->actingAs($admin)
        ->delete(route('organizations.admin.settings.staff.destroy', [
            'organization' => $organization->slug,
            'user' => $otherStaff->id,
        ]))
        ->assertNotFound();

    expect($otherOrganization->users()->whereKey($otherStaff->id)->exists())->toBeTrue();
});

it('allows platform administrators to manage memberships for any organization', function () {
    $organization = Organization::factory()->create();
    $platformAdmin = User::factory()->create();
    $platformAdmin->forceFill(['is_platform_admin' => true])->save();
    $staff = User::factory()->create();

    $this->actingAs($platformAdmin)
        ->post(route('organizations.admin.settings.staff.store', ['organization' => $organization->slug]), [
            'email' => $staff->email,
            'role' => 'admin',
        ])
        ->assertRedirect();

    expect($organization->users()->whereKey($staff->id)->firstOrFail()->pivot->role)->toBe('admin');
});
