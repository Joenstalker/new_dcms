<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantAppointmentChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public string $action;
    public array $appointment;

    public function __construct(string $tenantId, string $action, array $appointment)
    {
        $this->tenantId = $tenantId;
        $this->action = $action;
        $this->appointment = $appointment;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.appointments'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TenantAppointmentChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'action' => $this->action,
            'appointment' => $this->appointment,
        ];
    }
}
