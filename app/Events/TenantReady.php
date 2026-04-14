<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.tenants')];
    }

    public function broadcastAs(): string
    {
        return 'tenant.ready';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'subdomain' => $this->tenant->subdomain,
            'url' => $this->tenant->appUrl(),
        ];
    }
}
