<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Organization\GearItemController;
use App\Http\Controllers\Organization\OrganizationDashboardController;
use App\Http\Controllers\Organization\OrganizationSettingsController;
use App\Http\Controllers\Organization\OrganizationStaffController;
use App\Http\Controllers\Organization\PublicGearController;
use App\Http\Controllers\Organization\PublicOrganizationController;
use App\Http\Controllers\Platform\OrganizationController as PlatformOrganizationController;
use App\Http\Controllers\WorkspaceSelectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:login'])
    ->name('login.store');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/workspaces', WorkspaceSelectionController::class)
    ->middleware('auth')
    ->name('workspaces.index');

Route::middleware(['auth', 'can:manage-platform'])
    ->prefix('/admin/organizations')
    ->name('platform.organizations.')
    ->group(function () {
        Route::get('/', [PlatformOrganizationController::class, 'index'])->name('index');
        Route::get('/create', [PlatformOrganizationController::class, 'create'])->name('create');
        Route::post('/', [PlatformOrganizationController::class, 'store'])->name('store');
        Route::get('/{organization}', [PlatformOrganizationController::class, 'show'])->name('show');
        Route::get('/{organization}/edit', [PlatformOrganizationController::class, 'edit'])->name('edit');
        Route::put('/{organization}', [PlatformOrganizationController::class, 'update'])->name('update');
        Route::patch('/{organization}/status', [PlatformOrganizationController::class, 'toggleStatus'])->name('status');
    });

Route::get('/{organization:slug}/admin', OrganizationDashboardController::class)
    ->middleware(['auth', 'can:viewWorkspace,organization'])
    ->name('organizations.admin');

Route::scopeBindings()->group(function () {
    Route::get('/{organization:slug}/gear', [PublicGearController::class, 'index'])
        ->name('organizations.gear.index');
    Route::get('/{organization:slug}/gear/{gearItem}', [PublicGearController::class, 'show'])
        ->name('organizations.gear.show');
});

Route::scopeBindings()->group(function () {
    Route::middleware(['auth', 'can:viewWorkspace,organization'])
        ->prefix('/{organization:slug}/admin/settings')
        ->name('organizations.admin.settings.')
        ->group(function () {
            Route::get('/', [OrganizationSettingsController::class, 'edit'])->name('edit');
            Route::patch('/', [OrganizationSettingsController::class, 'update'])
                ->middleware('can:manageSettings,organization')
                ->name('update');
            Route::post('/staff', [OrganizationStaffController::class, 'store'])
                ->middleware('can:manageSettings,organization')
                ->name('staff.store');
            Route::patch('/staff/{user}', [OrganizationStaffController::class, 'update'])
                ->middleware('can:manageSettings,organization')
                ->name('staff.update');
            Route::delete('/staff/{user}', [OrganizationStaffController::class, 'destroy'])
                ->middleware('can:manageSettings,organization')
                ->name('staff.destroy');
        });
});

Route::scopeBindings()->group(function () {
    Route::resource('/{organization:slug}/admin/gear', GearItemController::class)
        ->except(['show'])
        ->parameters(['gear' => 'gearItem'])
        ->middleware(['auth', 'can:viewWorkspace,organization'])
        ->names([
            'index' => 'organizations.admin.gear.index',
            'create' => 'organizations.admin.gear.create',
            'store' => 'organizations.admin.gear.store',
            'edit' => 'organizations.admin.gear.edit',
            'update' => 'organizations.admin.gear.update',
            'destroy' => 'organizations.admin.gear.destroy',
        ]);
});

Route::get('/{organization:slug}', [PublicOrganizationController::class, 'show'])
    ->name('organizations.show');
