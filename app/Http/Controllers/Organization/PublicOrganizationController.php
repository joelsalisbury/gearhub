<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\View\View;

class PublicOrganizationController extends Controller
{
    public function show(Organization $organization): View
    {
        return view('organizations.show', [
            'organization' => $organization,
        ]);
    }
}
