<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantPatientChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public string $action;
    public array $patient;

    public function __construct(string $tenantId, string $action, array $patient)
    {
        $this->tenantId = $tenantId;
        $this->action = $action;
        $this->patient = $patient;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.patients'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TenantPatientChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'action' => $this->action,
            'patient' => $this->patient,
        ];
    }
}
