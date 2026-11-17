<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('grants and revokes platform administrator access for an existing user', function () {
    $user = User::factory()->create();

    $this->artisan('gearhub:platform-admin', ['email' => $user->email])
        ->expectsOutputToContain("Platform administrator access granted to {$user->email}.")
        ->assertSuccessful();

    expect($user->fresh()->isPlatformAdmin())->toBeTrue();

    $this->artisan('gearhub:platform-admin', [
        'email' => $user->email,
        '--revoke' => true,
    ])
        ->expectsOutputToContain("Platform administrator access revoked from {$user->email}.")
        ->assertSuccessful();

    expect($user->fresh()->isPlatformAdmin())->toBeFalse();
});

it('does not create an account when the command email is not registered', function () {
    $this->artisan('gearhub:platform-admin', ['email' => 'missing@example.test'])
        ->expectsOutputToContain('No GearHub user exists with email [missing@example.test].')
        ->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'missing@example.test']);
});
