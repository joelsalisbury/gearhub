<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Organization\GearItemController;
use App\Http\Controllers\Organization\OrganizationDashboardController;
use App\Http\Controllers\Organization\PublicOrganizationController;
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

Route::get('/{organization:slug}/admin', OrganizationDashboardController::class)
    ->middleware(['auth', 'can:viewWorkspace,organization'])
    ->name('organizations.admin');

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
