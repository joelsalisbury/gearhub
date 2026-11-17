<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\View\View;

class PublicOrganizationController extends Controller
{
    public function show(Organization $organization): View
    {
        abort_unless($organization->isActive(), 404);

        return view('organizations.show', [
            'organization' => $organization,
            'hasPublicGear' => $organization->gearItems()
                ->where('status', 'active')
                ->exists(),
        ]);
    }
}
