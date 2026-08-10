<?php

namespace App\Policies;

use App\Models\BonDeCommande;
use App\Models\User;

class BonDeCommandePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_bon_de_commande');
    }

    public function view(User $user, BonDeCommande $bonDeCommande): bool
    {
        return $user->hasPermissionTo('view_bon_de_commande');
    }
}
