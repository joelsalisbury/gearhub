<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewWorkspace(User $user, Organization $organization): bool
    {
        return $user->organizations()->whereKey($organization->getKey())->exists();
    }
}
