<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\View\View;

class OrganizationDashboardController extends Controller
{
    public function __invoke(Organization $organization): View
    {
        return view('organizations.admin.overview', [
            'organization' => $organization,
        ]);
    }
}
