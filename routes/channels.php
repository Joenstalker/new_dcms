<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('tenant.{tenantId}.appointments', function ($user, $tenantId) {
    if (!auth()->check() || !tenant()) {
        return false;
    }

    if ((string) tenant()->getTenantKey() !== (string) $tenantId) {
        return false;
    }

    return $user->can('view appointments') || $user->can('edit appointments');
});

Broadcast::channel('tenant.{tenantId}.patients', function ($user, $tenantId) {
    if (!auth()->check() || !tenant()) {
        return false;
    }

    if ((string) tenant()->getTenantKey() !== (string) $tenantId) {
        return false;
    }

    return $user->can('view patients') || $user->can('edit patients');
});

Broadcast::channel('tenant.{tenantId}.services', function ($user, $tenantId) {
    if (!auth()->check() || !tenant()) {
        return false;
    }

    if ((string) tenant()->getTenantKey() !== (string) $tenantId) {
        return false;
    }

    return $user->can('view services') || $user->can('edit services') || $user->can('create services');
});

Broadcast::channel('tenant.{tenantId}.treatments', function ($user, $tenantId) {
    if (!auth()->check() || !tenant()) {
        return false;
    }

    if ((string) tenant()->getTenantKey() !== (string) $tenantId) {
        return false;
    }

    return $user->can('view treatments') || $user->can('edit treatments') || $user->can('create treatments');
});

Broadcast::channel('tenant.{tenantId}.billing', function ($user, $tenantId) {
    if (!auth()->check() || !tenant()) {
        return false;
    }

    if ((string) tenant()->getTenantKey() !== (string) $tenantId) {
        return false;
    }

    return $user->can('view billing') || $user->can('create billing') || $user->can('edit billing');
});

Broadcast::channel('tenant.{tenantId}.staff', function ($user, $tenantId) {
    if (!auth()->check() || !tenant()) {
        return false;
    }

    if ((string) tenant()->getTenantKey() !== (string) $tenantId) {
        return false;
    }

    return $user->can('view staff') || $user->can('edit staff') || $user->can('create staff');
});

Broadcast::channel('support.ticket.{id}', function ($user, $id) {
    if (!\Illuminate\Support\Facades\Auth::check() || !tenant()) {
        return false;
    }

    if (!$user->hasRole('Owner') && !$user->can('access support chat')) {
        return false;
    }

    $ticket = \App\Models\SupportTicket::query()->find($id);
    if (!$ticket) {
        return false;
    }

    return (string) $ticket->tenant_id === (string) tenant()->getTenantKey();
});

Broadcast::channel('admin.support.tickets', function ($user) {
    return \Illuminate\Support\Facades\Auth::check();
});
