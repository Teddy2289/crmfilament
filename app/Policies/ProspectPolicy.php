<?php

namespace App\Policies;

use App\Models\Prospect;
use App\Models\User;

class ProspectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_prospect');
    }

    public function view(User $user, Prospect $prospect): bool
    {
        if ($user->isTeleprospecteur()) {
            return $prospect->teleprospecteur_id === $user->id;
        }

        return $user->hasPermissionTo('view_prospect');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_prospect');
    }

    public function update(User $user, Prospect $prospect): bool
    {
        return $user->hasPermissionTo('edit_prospect');
    }

    public function delete(User $user, Prospect $prospect): bool
    {
        return $user->hasPermissionTo('delete_prospect');
    }

    /**
     * Gate personnalisée : valider le questionnaire de financement.
     * Délégue à CrmProfile.can_validate_qf.
     */
    public function validerQf(User $user): bool
    {
        return $user->crmProfile?->can_validate_qf === true;
    }
}
