<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnlineBookingCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public array $appointment;

    public function __construct(string $tenantId, array $appointment)
    {
        $this->tenantId = $tenantId;
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
        return 'OnlineBookingCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'appointment' => $this->appointment,
        ];
    }
}
