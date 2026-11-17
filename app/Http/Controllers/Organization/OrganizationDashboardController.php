<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationDashboardController extends Controller
{
    public function __invoke(Request $request, Organization $organization): View
    {
        $user = $request->user();
        $staffOrganizations = $user->isAppAdmin()
            ? Organization::query()->orderBy('name')->get()
            : $user->organizations()->orderBy('name')->get();

        return view('organizations.admin.overview', [
            'organization' => $organization,
            'staffOrganizations' => $staffOrganizations,
        ]);
    }
}
