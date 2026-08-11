<?php

namespace App\Policies;

use App\Models\RendezVous;
use App\Models\User;

class RendezVousPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('rendez_vous.view_any');
    }

    public function view(User $user, RendezVous $rendezVous): bool
    {
        return $user->hasPermissionTo('rendez_vous.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rendez_vous.create');
    }

    public function update(User $user, RendezVous $rendezVous): bool
    {
        return $user->hasPermissionTo('rendez_vous.update');
    }
}
