<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantStaffChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public string $action;
    public array $staff;

    public function __construct(string $tenantId, string $action, array $staff)
    {
        $this->tenantId = $tenantId;
        $this->action = $action;
        $this->staff = $staff;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.staff'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TenantStaffChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'action' => $this->action,
            'staff' => $this->staff,
        ];
    }
}
