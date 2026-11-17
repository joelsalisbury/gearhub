<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\OrganizationRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizationStaffController extends Controller
{
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('manageSettings', $organization);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(array_column(OrganizationRole::cases(), 'value'))],
        ]);
        $user = User::query()->where('email', $validated['email'])->firstOrFail();

        $added = DB::transaction(function () use ($organization, $user, $validated): bool {
            Organization::query()->whereKey($organization->getKey())->lockForUpdate()->firstOrFail();

            if ($organization->users()->whereKey($user->getKey())->exists()) {
                return false;
            }

            $organization->users()->attach($user, ['role' => $validated['role']]);

            return true;
        });

        if (! $added) {
            return back()->withErrors([
                'email' => 'This user is already a member of the organization.',
            ])->withInput();
        }

        return back()->with('status', 'Staff member added.');
    }

    public function update(Request $request, Organization $organization, User $user): RedirectResponse
    {
        Gate::authorize('manageSettings', $organization);

        $validated = $request->validate([
            'role' => ['required', Rule::in(array_column(OrganizationRole::cases(), 'value'))],
        ]);

        DB::transaction(function () use ($organization, $user, $validated): void {
            Organization::query()->whereKey($organization->getKey())->lockForUpdate()->firstOrFail();
            $membership = $organization->users()->whereKey($user->getKey())->firstOrFail();

            if (
                $membership->pivot->role === OrganizationRole::Admin->value
                && $validated['role'] !== OrganizationRole::Admin->value
                && $organization->users()->wherePivot('role', OrganizationRole::Admin->value)->count() <= 1
            ) {
                throw ValidationException::withMessages([
                    'staff' => 'The last organization admin cannot be demoted.',
                ]);
            }

            $organization->users()->updateExistingPivot($user->getKey(), [
                'role' => $validated['role'],
            ]);
        });

        return back()->with('status', 'Staff role updated.');
    }

    public function destroy(Organization $organization, User $user): RedirectResponse
    {
        Gate::authorize('manageSettings', $organization);

        DB::transaction(function () use ($organization, $user): void {
            Organization::query()->whereKey($organization->getKey())->lockForUpdate()->firstOrFail();
            $membership = $organization->users()->whereKey($user->getKey())->firstOrFail();

            if (
                $membership->pivot->role === OrganizationRole::Admin->value
                && $organization->users()->wherePivot('role', OrganizationRole::Admin->value)->count() <= 1
            ) {
                throw ValidationException::withMessages([
                    'staff' => 'The last organization admin cannot be removed.',
                ]);
            }

            $organization->users()->detach($user->getKey());
        });

        return back()->with('status', 'Staff member removed.');
    }
}
