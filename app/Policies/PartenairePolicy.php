<?php

namespace App\Policies;

use App\Models\Partenaire;
use App\Models\User;

class PartenairePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_partenaire');
    }

    public function view(User $user, Partenaire $partenaire): bool
    {
        return $user->hasPermissionTo('view_partenaire');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_partenaire');
    }

    public function update(User $user, Partenaire $partenaire): bool
    {
        return $user->hasPermissionTo('edit_partenaire');
    }
}
