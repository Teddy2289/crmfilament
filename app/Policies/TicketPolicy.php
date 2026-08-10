<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_ticket');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('view_ticket');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_ticket');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('edit_ticket');
    }
}
