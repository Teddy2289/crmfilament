<?php

namespace App\Policies;

use App\Models\CampagnePhoning;
use App\Models\User;

class CampagnePhoningPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_campagne_phoning');
    }

    public function view(User $user, CampagnePhoning $campagnePhoning): bool
    {
        return $user->hasPermissionTo('view_campagne_phoning');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_campagne_phoning');
    }

    public function update(User $user, CampagnePhoning $campagnePhoning): bool
    {
        return $user->hasPermissionTo('edit_campagne_phoning');
    }
}
