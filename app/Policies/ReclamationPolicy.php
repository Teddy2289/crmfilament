<?php

namespace App\Policies;

use App\Models\ReclamationP8;
use App\Models\User;

class ReclamationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_reclamation');
    }

    public function view(User $user, ReclamationP8 $reclamation): bool
    {
        return $user->hasPermissionTo('view_reclamation');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_reclamation');
    }

    public function update(User $user, ReclamationP8 $reclamation): bool
    {
        return $user->hasPermissionTo('edit_reclamation');
    }
}
