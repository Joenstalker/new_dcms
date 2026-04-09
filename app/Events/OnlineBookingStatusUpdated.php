<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnlineBookingStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public bool $online_booking_enabled;

    public function __construct(string $tenantId, bool $onlineBookingEnabled)
    {
        $this->tenantId = $tenantId;
        $this->online_booking_enabled = $onlineBookingEnabled;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.' . $this->tenantId . '.booking'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OnlineBookingStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'online_booking_enabled' => $this->online_booking_enabled,
        ];
    }
}
