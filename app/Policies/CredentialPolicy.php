<?php

namespace App\Policies;

use App\Models\Credential;
use App\Models\Organization;
use App\Models\User;

class CredentialPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin() || $user->belongsToOrganization($organization);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Credential $credential): bool
    {
        return $this->canAccess($user, $credential->organization);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Organization $organization): bool
    {
        return $this->isOrgOwner($user, $organization);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Credential $credential): bool
    {
        return $this->isOrgOwner($user, $credential->organization);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Credential $credential): bool
    {
        return $this->isOrgOwner($user, $credential->organization);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Credential $credential): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Credential $credential): bool
    {
        return false;
    }

    private function canAccess(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin() || $user->belongsToOrganization($organization);
    }

    private function isOrgOwner(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin() || $user->isOwnerOfOrganization($organization);
    }
}
