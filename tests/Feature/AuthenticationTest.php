<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('authenticates staff and returns them to their intended page', function () {
    $user = User::factory()->create([
        'password' => 'local-password',
    ]);

    $this->get('/qcnemba/admin')->assertRedirectToRoute('login');

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'local-password',
    ])
        ->assertRedirect('/qcnemba/admin')
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
