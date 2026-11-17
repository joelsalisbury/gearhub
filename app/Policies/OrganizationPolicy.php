<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewWorkspace(User $user, Organization $organization): bool
    {
        return $organization->isActive()
            && $user->organizations()->whereKey($organization->getKey())->exists();
    }

    public function manageSettings(User $user, Organization $organization): bool
    {
        return $organization->isActive()
            && $user->organizations()
                ->whereKey($organization->getKey())
                ->wherePivot('role', 'admin')
                ->exists();
    }
}
