<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantMedicalRecordChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public string $action;
    public array $medicalRecord;

    public function __construct(string $tenantId, string $action, array $medicalRecord)
    {
        $this->tenantId = $tenantId;
        $this->action = $action;
        $this->medicalRecord = $medicalRecord;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.medical-records'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TenantMedicalRecordChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'action' => $this->action,
            'medicalRecord' => $this->medicalRecord,
        ];
    }
}
