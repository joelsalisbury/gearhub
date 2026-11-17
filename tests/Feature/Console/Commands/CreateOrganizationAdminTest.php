<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates an organization admin through interactive prompts', function () {
    $organization = Organization::factory()->create(['slug' => 'tool-library']);

    $this->artisan('gearhub:make-admin')
        ->expectsQuestion('Organization slug', 'tool-library')
        ->expectsQuestion('Name', 'Jamie Volunteer')
        ->expectsQuestion('Email address', 'jamie@example.test')
        ->expectsQuestion('Password', 'a-long-local-password')
        ->expectsQuestion('Confirm password', 'a-long-local-password')
        ->expectsOutputToContain('Admin user jamie@example.test created for '.$organization->name.'.')
        ->assertSuccessful();

    $user = User::where('email', 'jamie@example.test')->firstOrFail();

    expect(Hash::check('a-long-local-password', $user->password))->toBeTrue();
    expect($user->organizations()->whereKey($organization->id)->firstOrFail()->pivot->role)->toBe('admin');
});

it('creates an organization admin from one-line options', function () {
    $organization = Organization::factory()->create(['slug' => 'camera-club']);
    putenv('GEARHUB_TEST_ADMIN_PASSWORD=another-long-password');

    try {
        $this->artisan('gearhub:make-admin', [
            '--organization' => 'camera-club',
            '--name' => 'Taylor Volunteer',
            '--email' => 'taylor@example.test',
            '--password-env' => 'GEARHUB_TEST_ADMIN_PASSWORD',
            '--no-interaction' => true,
        ])->assertSuccessful();
    } finally {
        putenv('GEARHUB_TEST_ADMIN_PASSWORD');
    }

    $user = User::where('email', 'taylor@example.test')->firstOrFail();

    expect(Hash::check('another-long-password', $user->password))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'name' => 'Taylor Volunteer',
        'email' => 'taylor@example.test',
    ]);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
});

it('creates a platform admin from one-line options without organization membership', function () {
    putenv('GEARHUB_TEST_ADMIN_PASSWORD=another-long-password');

    try {
        $this->artisan('gearhub:make-admin', [
            '--app' => true,
            '--name' => 'Morgan Operator',
            '--email' => 'morgan@example.test',
            '--password-env' => 'GEARHUB_TEST_ADMIN_PASSWORD',
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('Platform admin user morgan@example.test created.')
            ->assertSuccessful();
    } finally {
        putenv('GEARHUB_TEST_ADMIN_PASSWORD');
    }

    $user = User::where('email', 'morgan@example.test')->firstOrFail();

    expect(Hash::check('another-long-password', $user->password))->toBeTrue()
        ->and($user->isPlatformAdmin())->toBeTrue()
        ->and($user->organizations()->exists())->toBeFalse();
});

it('rejects a platform admin creation command that also specifies an organization', function () {
    $this->artisan('gearhub:make-admin', [
        '--app' => true,
        '--organization' => 'tool-library',
        '--name' => 'Morgan Operator',
        '--email' => 'morgan@example.test',
        '--password' => 'another-long-password',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('--platform cannot be used with --organization.')
        ->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'morgan@example.test']);
});

it('rejects a missing organization without creating an account', function () {
    $this->artisan('gearhub:make-admin', [
        '--organization' => 'missing-org',
        '--name' => 'Taylor Volunteer',
        '--email' => 'taylor@example.test',
        '--password' => 'another-long-password',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('No organization exists with slug [missing-org].')
        ->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'taylor@example.test']);
});

it('rejects mismatched interactive passwords without creating an account', function () {
    Organization::factory()->create(['slug' => 'tool-library']);

    $this->artisan('gearhub:make-admin')
        ->expectsQuestion('Organization slug', 'tool-library')
        ->expectsQuestion('Name', 'Jamie Volunteer')
        ->expectsQuestion('Email address', 'jamie@example.test')
        ->expectsQuestion('Password', 'a-long-local-password')
        ->expectsQuestion('Confirm password', 'a-different-password')
        ->expectsOutputToContain('The passwords do not match.')
        ->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'jamie@example.test']);
});
