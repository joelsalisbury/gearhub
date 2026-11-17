<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    public function edit(Organization $organization): View
    {
        Gate::authorize('manageSettings', $organization);

        $organization->load(['parent', 'users']);

        return view('organizations.admin.settings', [
            'organization' => $organization,
            'adminCount' => $organization->users()->wherePivot('role', 'admin')->count(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('manageSettings', $organization);

        $organization->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'timezone' => ['required', 'timezone'],
        ]));

        return redirect()
            ->route('organizations.admin.settings.edit', ['organization' => $organization->slug])
            ->with('status', 'Organization settings updated.');
    }
}
