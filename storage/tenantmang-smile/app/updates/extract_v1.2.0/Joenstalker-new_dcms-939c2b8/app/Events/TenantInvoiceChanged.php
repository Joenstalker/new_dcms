<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantInvoiceChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public string $action;
    public array $invoice;

    public function __construct(string $tenantId, string $action, array $invoice)
    {
        $this->tenantId = $tenantId;
        $this->action = $action;
        $this->invoice = $invoice;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.billing'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TenantInvoiceChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'action' => $this->action,
            'invoice' => $this->invoice,
        ];
    }
}
