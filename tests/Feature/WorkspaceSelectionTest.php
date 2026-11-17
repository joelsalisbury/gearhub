<?php

use App\Models\Organization;
use App\Models\User;
use App\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects a user with one active organization to its workspace', function () {
    $organization = Organization::factory()->create(['slug' => 'tool-library']);
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->get(route('workspaces.index'))
        ->assertRedirect(route('organizations.admin', ['organization' => $organization->slug]));
});

it('lets users select from only their active organization memberships', function () {
    $firstOrganization = Organization::factory()->create(['name' => 'Tool Library']);
    $secondOrganization = Organization::factory()->create(['name' => 'Camera Club']);
    $inactiveOrganization = Organization::factory()->create([
        'name' => 'Inactive Club',
        'is_active' => false,
    ]);
    $unrelatedOrganization = Organization::factory()->create(['name' => 'Other Group']);
    $user = User::factory()->create();
    $user->organizations()->attach($firstOrganization, ['role' => OrganizationRole::Admin->value]);
    $user->organizations()->attach($secondOrganization, ['role' => OrganizationRole::Manager->value]);
    $user->organizations()->attach($inactiveOrganization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->get(route('workspaces.index'))
        ->assertOk()
        ->assertSee($firstOrganization->name)
        ->assertSee($secondOrganization->name)
        ->assertDontSee($inactiveOrganization->name)
        ->assertDontSee($unrelatedOrganization->name);
});

it('shows a useful empty state to users with no active organization memberships', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('workspaces.index'))
        ->assertOk()
        ->assertSee('No active workspaces yet')
        ->assertSee('Ask an organization administrator');
});

it('sends platform administrators to platform organization management', function () {
    $platformAdmin = User::factory()->create();
    $platformAdmin->forceFill(['is_platform_admin' => true])->save();

    $this->actingAs($platformAdmin)
        ->get(route('workspaces.index'))
        ->assertRedirectToRoute('platform.organizations.index');
});

it('sends a single-member user to their workspace after sign in', function () {
    $organization = Organization::factory()->create(['slug' => 'local-tools']);
    $user = User::factory()->create(['password' => 'local-password']);
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Manager->value]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'local-password',
    ])->assertRedirect(route('organizations.admin', ['organization' => $organization->slug]));
});

it('sends users with no memberships to the selector after sign in', function () {
    $user = User::factory()->create(['password' => 'local-password']);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'local-password',
    ])->assertRedirectToRoute('workspaces.index');
});

it('sends users with multiple active memberships to the selector after sign in', function () {
    $first = Organization::factory()->create();
    $second = Organization::factory()->create();
    $user = User::factory()->create(['password' => 'local-password']);
    $user->organizations()->attach($first, ['role' => OrganizationRole::Admin->value]);
    $user->organizations()->attach($second, ['role' => OrganizationRole::Manager->value]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'local-password',
    ])->assertRedirectToRoute('workspaces.index');
});
