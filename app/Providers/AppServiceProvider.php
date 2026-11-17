<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isPlatformAdmin() ? true : null;
        });

        Gate::define('manage-platform', fn (User $user): bool => $user->isPlatformAdmin());
        Gate::policy(Organization::class, OrganizationPolicy::class);

        View::composer('layouts.organization', function (ViewContract $view): void {
            $user = request()->user();

            if ($user === null) {
                return;
            }

            $view->with('staffOrganizations', $user->isPlatformAdmin()
                ? Organization::query()->orderBy('name')->get()
                : $user->organizations()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(Str::transliterate(
                $request->string('email')->lower()->toString().'|'.$request->ip(),
            ));
        });
    }
}
