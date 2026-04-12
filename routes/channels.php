<?php

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Resolve current tenant key for private channel auth.
 * /broadcasting/auth often runs before tenancy middleware, so tenant() may be null
 * even on a tenant host — use the domains table + host fallback.
 */
$resolveTenantKeyForBroadcasting = static function (): ?string {
    if (tenant()) {
        return (string) tenant()->getTenantKey();
    }

    $host = request()->getHost();
    if ($host !== '') {
        $domain = Domain::query()->where('domain', $host)->first();
        if ($domain) {
            return (string) $domain->tenant_id;
        }

        if (str_ends_with($host, '.localhost')) {
            $sub = str_replace('.localhost', '', $host);
            if ($sub !== '' && $sub !== 'www') {
                return $sub;
            }
        }
    }

    $sessionTenantId = (string) session('tenant_authenticated_tenant_id', '');

    return $sessionTenantId !== '' ? $sessionTenantId : null;
};

$matchesTenantContext = function ($tenantId): bool {
    if (tenant() && (string) tenant()->getTenantKey() === (string) $tenantId) {
        return true;
    }

    $sessionTenantId = (string) session('tenant_authenticated_tenant_id', '');

    return $sessionTenantId !== '' && $sessionTenantId === (string) $tenantId;
};

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tenant.{tenantId}.appointments', function ($user, $tenantId) use ($matchesTenantContext) {
    if (! auth()->check()) {
        return false;
    }

    if (! $matchesTenantContext($tenantId)) {
        return false;
    }

    return $user->can('view appointments') || $user->can('edit appointments');
});

Broadcast::channel('tenant.{tenantId}.patients', function ($user, $tenantId) use ($matchesTenantContext) {
    if (! auth()->check()) {
        return false;
    }

    if (! $matchesTenantContext($tenantId)) {
        return false;
    }

    return $user->can('view patients') || $user->can('edit patients');
});

Broadcast::channel('tenant.{tenantId}.services', function ($user, $tenantId) use ($matchesTenantContext) {
    if (! auth()->check()) {
        return false;
    }

    if (! $matchesTenantContext($tenantId)) {
        return false;
    }

    return $user->can('view services') || $user->can('edit services') || $user->can('create services');
});

Broadcast::channel('tenant.{tenantId}.treatments', function ($user, $tenantId) use ($matchesTenantContext) {
    if (! auth()->check()) {
        return false;
    }

    if (! $matchesTenantContext($tenantId)) {
        return false;
    }

    return $user->can('view treatments') || $user->can('edit treatments') || $user->can('create treatments');
});

Broadcast::channel('tenant.{tenantId}.billing', function ($user, $tenantId) use ($matchesTenantContext) {
    if (! auth()->check()) {
        return false;
    }

    if (! $matchesTenantContext($tenantId)) {
        return false;
    }

    return $user->can('view billing') || $user->can('create billing') || $user->can('edit billing');
});

Broadcast::channel('tenant.{tenantId}.staff', function ($user, $tenantId) use ($matchesTenantContext) {
    if (! auth()->check()) {
        return false;
    }

    if (! $matchesTenantContext($tenantId)) {
        return false;
    }

    return $user->can('view staff') || $user->can('edit staff') || $user->can('create staff');
});

Broadcast::channel('tenant.{tenantId}.medical-records', function ($user, $tenantId) use ($matchesTenantContext) {
    if (! auth()->check()) {
        return false;
    }

    if (! $matchesTenantContext($tenantId)) {
        return false;
    }

    return $user->can('view medical records') || $user->can('edit medical records') || $user->can('create medical records');
});

Broadcast::channel('support.ticket.{id}', function ($user, $id) use ($resolveTenantKeyForBroadcasting) {
    if (! Auth::check()) {
        return false;
    }

    if (! $user->hasRole('Owner') && ! $user->can('access support chat')) {
        return false;
    }

    $ticket = SupportTicket::query()->find($id);
    if (! $ticket) {
        return false;
    }

    $tenantKey = $resolveTenantKeyForBroadcasting();

    return $tenantKey !== null && (string) $ticket->tenant_id === $tenantKey;
});

Broadcast::channel('admin.support.tickets', function ($user) {
    return (bool) ($user && $user->is_admin);
});
