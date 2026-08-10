<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_client');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('view_client');
    }
}
