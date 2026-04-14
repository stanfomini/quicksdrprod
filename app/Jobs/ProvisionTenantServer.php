<?php

namespace App\Jobs;

use App\Enums\TenantStatus;
use App\Events\TenantReady;
use App\Mail\TenantCredentials;
use App\Models\Tenant;
use App\Services\Provisioning\TenantProvisioner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Top-level orchestrator for one tenant's provisioning pipeline.
 *
 * The job advances through `$tenant->status_step` one step at a time. For
 * async steps (server readiness, SSL, recipes) it records progress and
 * self-releases with a 60s delay so Forge has time to make progress. On
 * every wake, it re-reads the tenant's state and resumes at the last
 * recorded step.
 *
 * Tries = 120 with backoff 60 means we tolerate up to ~120 min for the
 * whole pipeline (typical run is ~12-15 min).
 */
class ProvisionTenantServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 120;

    public int $backoff = 60;

    public int $timeout = 180;

    public function __construct(public Tenant $tenant)
    {
        $this->onQueue('orchestrator');
    }

    public function handle(TenantProvisioner $provisioner): void
    {
        $this->tenant->refresh();

        if ($this->tenant->status === TenantStatus::Ready || $this->tenant->status === TenantStatus::Destroyed) {
            Log::info('ProvisionTenantServer: nothing to do', [
                'tenant_id' => $this->tenant->id,
                'status' => $this->tenant->status->value,
            ]);

            return;
        }

        try {
            $this->runStep($provisioner);
        } catch (Throwable $e) {
            $this->tenant->markFailed($this->tenant->status_step, $e->getMessage());
            Log::error('ProvisionTenantServer failed', [
                'tenant_id' => $this->tenant->id,
                'step' => $this->tenant->status_step,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function runStep(TenantProvisioner $p): void
    {
        $t = $this->tenant;
        $step = $t->status_step;

        // Before step 1: initialize.
        if ($step < 1) {
            $t->status = TenantStatus::Provisioning;
            $t->recordStep(1, 'starting');
            $this->release(1);

            return;
        }

        if ($step < 2) {
            $p->validateAndReserve($t);
            $this->release(1);

            return;
        }

        if ($step < 3) {
            $p->ensureServer($t);
            $this->release(60);   // poll for readiness

            return;
        }

        if ($step === 3) {
            if (! $p->serverIsReady($t)) {
                $this->release(60);

                return;
            }
            $t->recordStep(3, 'server ready');
            $this->release(1);

            return;
        }

        if ($step < 5) {
            $p->ensureDnsRecords($t);
            $this->release(1);

            return;
        }

        if ($step < 6) {
            $p->ensurePrimarySite($t);
            $this->release(1);

            return;
        }

        if ($step < 7) {
            $p->ensureGitRepo($t);
            $this->release(1);

            return;
        }

        if ($step < 8) {
            $p->ensureDatabase($t);
            $this->release(1);

            return;
        }

        if ($step < 9) {
            $p->writeEnvFile($t);
            $this->release(1);

            return;
        }

        if ($step < 10) {
            $p->requestPrimarySsl($t);
            $this->release(60);  // let Let's Encrypt process

            return;
        }

        if ($step < 11) {
            $p->ensureWebsocketSite($t);
            $this->release(60);

            return;
        }

        if ($step < 12) {
            $p->ensureDeployScript($t);
            $t->recordStep(11, 'deploy complete');
            $this->release(1);

            return;
        }

        if ($step < 14) {
            $p->ensureDaemons($t);
            $this->release(1);

            return;
        }

        if ($step < 15) {
            $p->runPythonRecipe($t);
            $this->release(180);  // Python install takes a while

            return;
        }

        if ($step < 16) {
            $p->runReverbRecipe($t);
            $this->release(60);

            return;
        }

        if ($step < 17) {
            $p->enableScheduler($t);
            $this->release(1);

            return;
        }

        if ($step < 18) {
            $p->seedAdmin($t);
            $this->release(30);  // let the recipe complete

            return;
        }

        if ($step < 19) {
            // Step 18: credentials email. Dispatches a queued Mailable.
            Mail::to($t->admin_email)->queue(new TenantCredentials($t));
            $t->recordStep(18, 'credentials email queued');
            $this->release(1);

            return;
        }

        if ($step < 20) {
            $p->notifyPartnerstack($t);
            $this->release(1);

            return;
        }

        if ($step < 21) {
            broadcast(new TenantReady($t));
            $t->recordStep(20, 'TenantReady broadcast');
            $this->release(1);

            return;
        }

        if ($step < 22) {
            $p->markReady($t);
            // Done — no re-queue.
        }
    }
}
