<?php

namespace App\Policies;

use App\Models\RendezVous;
use App\Models\User;

class RendezVousPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_rendez_vous');
    }

    public function view(User $user, RendezVous $rendezVous): bool
    {
        return $user->hasPermissionTo('view_rendez_vous');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_rendez_vous');
    }

    public function update(User $user, RendezVous $rendezVous): bool
    {
        return $user->hasPermissionTo('edit_rendez_vous');
    }
}
