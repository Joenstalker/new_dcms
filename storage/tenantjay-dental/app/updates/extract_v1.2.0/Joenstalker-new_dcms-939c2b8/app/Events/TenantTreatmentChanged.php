<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantTreatmentChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public string $action;
    public array $treatment;

    public function __construct(string $tenantId, string $action, array $treatment)
    {
        $this->tenantId = $tenantId;
        $this->action = $action;
        $this->treatment = $treatment;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.treatments'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TenantTreatmentChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'action' => $this->action,
            'treatment' => $this->treatment,
        ];
    }
}
