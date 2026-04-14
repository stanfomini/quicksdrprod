<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Provisioning\TenantProvisioner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Destroys a tenant's Forge server + Cloudflare DNS records after the
 * 7-day export-only grace period has expired. Invoked from the
 * tenants:destroy-expired scheduled command.
 */
class DestroyTenantServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Tenant $tenant)
    {
        $this->onQueue('orchestrator');
    }

    public function handle(TenantProvisioner $provisioner): void
    {
        $this->tenant->refresh();

        if ($this->tenant->status->value === 'destroyed') {
            Log::info('DestroyTenantServer: already destroyed', [
                'tenant_id' => $this->tenant->id,
            ]);

            return;
        }

        $provisioner->destroy($this->tenant);

        Log::info('DestroyTenantServer: destroyed', [
            'tenant_id' => $this->tenant->id,
        ]);
    }
}
