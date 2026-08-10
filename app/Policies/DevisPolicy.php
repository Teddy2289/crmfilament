<?php

namespace App\Policies;

use App\Models\Devis;
use App\Models\User;

class DevisPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_devis');
    }

    public function view(User $user, Devis $devis): bool
    {
        return $user->hasPermissionTo('view_devis');
    }
}
