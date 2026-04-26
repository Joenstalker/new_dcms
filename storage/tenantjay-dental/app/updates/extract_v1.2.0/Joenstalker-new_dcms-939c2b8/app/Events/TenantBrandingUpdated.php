<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantBrandingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public array $payload;

    public function __construct(string $tenantId, array $payload)
    {
        $this->tenantId = $tenantId;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.' . $this->tenantId . '.branding'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TenantBrandingUpdated';
    }

    public function broadcastWith(): array
    {
        return array_merge([
            'tenant_id' => $this->tenantId,
        ], $this->payload);
    }
}
