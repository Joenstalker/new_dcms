<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    /**
     * Determine whether the user can view a support ticket.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        $tenant = tenant();

        if (! $tenant) {
            return false;
        }

        return (string) $ticket->tenant_id === (string) $tenant->id;
    }

    /**
     * Determine whether the user can update/reply to a support ticket.
     */
    public function update(User $user, SupportTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
