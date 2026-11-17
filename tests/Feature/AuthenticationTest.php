<?php

use App\Models\Organization;
use App\Models\User;
use App\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('authenticates staff and returns them to their intended page', function () {
    $organization = Organization::factory()->create(['slug' => 'tool-library']);
    $user = User::factory()->create([
        'password' => 'local-password',
    ]);
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->get(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertRedirectToRoute('login');

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'local-password',
    ])
        ->assertRedirect(route('organizations.admin', ['organization' => $organization->slug]))
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid staff credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])
        ->assertRedirectBackWithErrors(['email']);

    $this->assertGuest();
});

it('signs staff out and clears their authenticated session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirectToRoute('home');

    $this->assertGuest();
});
