<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired every time Tenant::recordStep() advances the status_step so the
 * admin UI can live-update during the 8-15 minute provisioning pipeline.
 * Separate from TenantReady (which only fires once, at step 20).
 */
class TenantStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public int $step,
        public string $message,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.tenants')];
    }

    public function broadcastAs(): string
    {
        return 'tenant.status-changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'status' => $this->tenant->status->value,
            'step' => $this->step,
            'message' => $this->message,
        ];
    }
}
