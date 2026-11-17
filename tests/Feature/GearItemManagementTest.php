<?php

use App\Models\Organization;
use App\Models\Reservation;
use App\Models\User;
use App\OrganizationRole;
use Database\Seeders\GearItemSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('shows inventory to organization staff, including unavailable gear', function () {
    $organization = Organization::factory()->create(['slug' => 'town-tools']);
    $otherOrganization = Organization::factory()->create(['slug' => 'camera-club']);
    $bike = $organization->gearItems()->create([
        'name' => 'Trail bike medium',
        'category' => 'Bikes',
        'identifier' => 'BIKE-M',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $organization->gearItems()->create([
        'name' => 'Unavailable tool kit',
        'quantity' => 1,
        'status' => 'unavailable',
    ]);
    $otherOrganization->gearItems()->create([
        'name' => 'Camera tripod',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->get(route('organizations.admin.gear.index', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee('Trail bike medium')
        ->assertSee('BIKE-M')
        ->assertSee('Unavailable tool kit')
        ->assertSee(route('organizations.admin.gear.edit', [
            'organization' => $organization->slug,
            'gearItem' => $bike,
        ]))
        ->assertDontSee('Camera tripod')
        ->assertSee('No photo');
});

it('shows a clear empty state when an organization has no gear', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Manager->value]);

    $this->actingAs($user)
        ->get(route('organizations.admin.gear.index', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee('No gear yet')
        ->assertSee(route('organizations.admin.gear.create', ['organization' => $organization->slug]));
});

it('paginates inventory for the active organization', function () {
    $organization = Organization::factory()->create(['slug' => 'town-tools']);
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    foreach (range(1, 16) as $number) {
        $organization->gearItems()->create([
            'name' => sprintf('Shared tool %02d', $number),
            'quantity' => 1,
            'status' => 'active',
        ]);
    }

    $index = route('organizations.admin.gear.index', ['organization' => $organization->slug]);

    $this->actingAs($user)
        ->get($index)
        ->assertOk()
        ->assertSee('Shared tool 01')
        ->assertDontSee('Shared tool 16')
        ->assertSee('Next');

    $this->get($index.'?page=2')
        ->assertOk()
        ->assertSee('Shared tool 16');
});

it('allows platform admins to view inventory without organization membership', function () {
    $organization = Organization::factory()->create();
    $organization->gearItems()->create([
        'name' => 'Platform admin viewable gear',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $user = User::factory()->create();
    $user->forceFill(['is_platform_admin' => true])->save();

    $this->actingAs($user)
        ->get(route('organizations.admin.gear.index', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee('Platform admin viewable gear')
        ->assertSee($organization->name);

    expect($user->organizations()->exists())->toBeFalse();
});

it('allows managers to create and edit inventory', function () {
    $organization = Organization::factory()->create(['slug' => 'town-tools']);
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Manager->value]);

    $this->actingAs($user)
        ->post(route('organizations.admin.gear.store', ['organization' => $organization->slug]), [
            'name' => 'Loaner helmet',
            'description' => 'Adult bicycle helmet.',
            'category' => 'Helmets',
            'identifier' => 'HELMET-01',
            'quantity' => 10,
            'status' => 'active',
            'notes' => 'Stored in the equipment room.',
        ])
        ->assertRedirectToRoute('organizations.admin.gear.index', ['organization' => $organization->slug]);

    $gearItem = $organization->gearItems()->where('identifier', 'HELMET-01')->firstOrFail();

    $this->assertDatabaseHas('gear_items', [
        'id' => $gearItem->id,
        'organization_id' => $organization->id,
        'quantity' => 10,
        'status' => 'active',
    ]);

    $this->put(route('organizations.admin.gear.update', [
        'organization' => $organization->slug,
        'gearItem' => $gearItem,
    ]), [
        'name' => 'Loaner helmet - youth',
        'description' => 'Youth bicycle helmet.',
        'category' => 'Helmets',
        'identifier' => 'HELMET-01',
        'quantity' => 8,
        'status' => 'unavailable',
        'notes' => 'Being inspected.',
    ])
        ->assertRedirectToRoute('organizations.admin.gear.edit', [
            'organization' => $organization->slug,
            'gearItem' => $gearItem,
        ]);

    $this->assertDatabaseHas('gear_items', [
        'id' => $gearItem->id,
        'name' => 'Loaner helmet - youth',
        'quantity' => 8,
        'status' => 'unavailable',
    ]);
});

it('requires a name and a positive quantity', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->post(route('organizations.admin.gear.store', ['organization' => $organization->slug]), [
            'quantity' => 0,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['name', 'quantity']);

    expect($organization->gearItems()->count())->toBe(0);
});

it('searches by name or identifier and filters by category and status', function () {
    $organization = Organization::factory()->create(['slug' => 'community-library']);
    $bike = $organization->gearItems()->create([
        'name' => 'Medium trail bike',
        'category' => 'Bikes',
        'identifier' => 'BIKE-M',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $helmet = $organization->gearItems()->create([
        'name' => 'Adult helmet',
        'category' => 'Helmets',
        'identifier' => 'HELMET-01',
        'quantity' => 1,
        'status' => 'unavailable',
    ]);
    $organization->gearItems()->create([
        'name' => 'Spare helmet',
        'category' => 'Helmets',
        'identifier' => 'HELMET-02',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Manager->value]);
    $index = route('organizations.admin.gear.index', ['organization' => $organization->slug]);

    $this->actingAs($user)
        ->get($index.'?q=BIKE-M&category=Bikes&status=active')
        ->assertOk()
        ->assertSee($bike->name)
        ->assertDontSee($helmet->name)
        ->assertSee('BIKE-M');

    $this->get($index.'?q=Adult&category=Helmets&status=unavailable')
        ->assertOk()
        ->assertSee($helmet->name)
        ->assertDontSee($bike->name)
        ->assertDontSee('Spare helmet');
});

it('stores, replaces, and removes gear photos', function () {
    Storage::fake('public');

    $organization = Organization::factory()->create(['slug' => 'town-tools']);
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);
    $store = route('organizations.admin.gear.store', ['organization' => $organization->slug]);

    $this->actingAs($user)
        ->post($store, [
            'name' => 'Medium trail bike',
            'quantity' => 1,
            'status' => 'active',
            'photo' => UploadedFile::fake()->image('bike.jpg'),
        ])
        ->assertRedirectToRoute('organizations.admin.gear.index', ['organization' => $organization->slug]);

    $gearItem = $organization->gearItems()->where('name', 'Medium trail bike')->firstOrFail();
    $originalPhotoPath = $gearItem->image_path;

    expect($originalPhotoPath)->toBeString();
    Storage::disk('public')->assertExists($originalPhotoPath);

    $this->get(route('organizations.admin.gear.edit', [
        'organization' => $organization->slug,
        'gearItem' => $gearItem,
    ]))
        ->assertOk()
        ->assertSee(Storage::disk('public')->url($originalPhotoPath));

    $this->put(route('organizations.admin.gear.update', [
        'organization' => $organization->slug,
        'gearItem' => $gearItem,
    ]), [
        'name' => 'Medium trail bike',
        'quantity' => 1,
        'status' => 'active',
        'photo' => UploadedFile::fake()->image('replacement.jpg'),
    ])->assertRedirect();

    $replacementPhotoPath = $gearItem->fresh()->image_path;

    expect($replacementPhotoPath)->not->toBe($originalPhotoPath);
    Storage::disk('public')->assertMissing($originalPhotoPath);
    Storage::disk('public')->assertExists($replacementPhotoPath);

    $this->delete(route('organizations.admin.gear.destroy', [
        'organization' => $organization->slug,
        'gearItem' => $gearItem,
    ]))->assertRedirectToRoute('organizations.admin.gear.index', ['organization' => $organization->slug]);

    Storage::disk('public')->assertMissing($replacementPhotoPath);
    $this->assertDatabaseMissing('gear_items', ['id' => $gearItem->id]);
});

it('prevents deleting gear that has reservation history', function () {
    $organization = Organization::factory()->create();
    $gearItem = $organization->gearItems()->create([
        'name' => 'Loaner helmet',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $reservation = Reservation::factory()->for($organization)->create();
    $reservation->items()->create(['gear_item_id' => $gearItem->id, 'quantity' => 1]);
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->delete(route('organizations.admin.gear.destroy', [
            'organization' => $organization->slug,
            'gearItem' => $gearItem,
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'gear' => 'Gear with reservation history cannot be deleted. Mark it unavailable instead.',
        ]);

    $this->assertModelExists($gearItem);
});

it('rejects non-image photo uploads', function () {
    Storage::fake('public');

    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->post(route('organizations.admin.gear.store', ['organization' => $organization->slug]), [
            'name' => 'Not a photo',
            'quantity' => 1,
            'status' => 'active',
            'photo' => UploadedFile::fake()->create('notes.txt', 5, 'text/plain'),
        ])
        ->assertSessionHasErrors('photo');

    expect($organization->gearItems()->count())->toBe(0);
    Storage::disk('public')->assertEmpty();
});

it('rejects photos larger than five megabytes', function () {
    Storage::fake('public');

    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($organization, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->post(route('organizations.admin.gear.store', ['organization' => $organization->slug]), [
            'name' => 'Oversized photo',
            'quantity' => 1,
            'status' => 'active',
            'photo' => UploadedFile::fake()->image('large.jpg')->size(5121),
        ])
        ->assertSessionHasErrors('photo');

    expect($organization->gearItems()->count())->toBe(0);
    Storage::disk('public')->assertEmpty();
});

it('denies nonmembers access to inventory and creation', function () {
    $organization = Organization::factory()->create();
    $item = $organization->gearItems()->create([
        'name' => 'Shared hammer',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('organizations.admin.gear.index', ['organization' => $organization->slug]))
        ->assertForbidden();

    $this->post(route('organizations.admin.gear.store', ['organization' => $organization->slug]), [
        'name' => 'Unauthorized item',
        'quantity' => 1,
        'status' => 'active',
    ])->assertForbidden();

    $this->assertDatabaseMissing('gear_items', ['name' => 'Unauthorized item']);
    $this->assertModelExists($item);
});

it('cannot access or modify another organizations gear by item id', function () {
    $ownerOrganization = Organization::factory()->create(['slug' => 'owner-org']);
    $otherOrganization = Organization::factory()->create(['slug' => 'other-org']);
    $item = $ownerOrganization->gearItems()->create([
        'name' => 'Owner bike',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $user = User::factory()->create();
    $user->organizations()->attach($otherOrganization, ['role' => OrganizationRole::Admin->value]);
    $editUrl = route('organizations.admin.gear.edit', [
        'organization' => $otherOrganization->slug,
        'gearItem' => $item,
    ]);

    $this->actingAs($user)
        ->get($editUrl)
        ->assertNotFound();

    $this->put(route('organizations.admin.gear.update', [
        'organization' => $otherOrganization->slug,
        'gearItem' => $item,
    ]), [
        'name' => 'Changed by another organization',
        'quantity' => 1,
        'status' => 'active',
    ])->assertNotFound();

    $this->delete(route('organizations.admin.gear.destroy', [
        'organization' => $otherOrganization->slug,
        'gearItem' => $item,
    ]))->assertNotFound();

    $this->assertDatabaseHas('gear_items', [
        'id' => $item->id,
        'organization_id' => $ownerOrganization->id,
        'name' => 'Owner bike',
    ]);
});

it('does not grant access to child inventory through parent membership', function () {
    $parent = Organization::factory()->create();
    $child = Organization::factory()->create(['parent_id' => $parent->id]);
    $child->gearItems()->create([
        'name' => 'Child organization item',
        'quantity' => 1,
        'status' => 'active',
    ]);
    $user = User::factory()->create();
    $user->organizations()->attach($parent, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($user)
        ->get(route('organizations.admin.gear.index', ['organization' => $child->slug]))
        ->assertForbidden();
});

it('seeds repeatable example gear only when the pilot organization exists', function () {
    $this->seed(OrganizationSeeder::class);
    $this->seed(GearItemSeeder::class);
    $this->seed(GearItemSeeder::class);

    $organization = Organization::query()->where('slug', 'qcnemba')->firstOrFail();

    expect($organization->gearItems()->count())->toBe(4)
        ->and($organization->gearItems()->where('identifier', 'HELMET-LOANER')->value('quantity'))->toBe(10)
        ->and($organization->gearItems()->where('identifier', 'TOOL-KIT')->value('status'))->toBe('unavailable');
});
