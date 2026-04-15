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

        // Each step runs exactly once. After running, advance() bumps
        // status_step to the next gate so the subsequent iteration falls
        // into the next branch rather than looping back into this one.
        // Polling branches don't advance until their condition is met.

        match (true) {
            $t->status_step < 1 => $this->step(1, function () use ($t) {
                $t->status = TenantStatus::Provisioning;
                $t->recordStep(1, 'starting');
            }, releaseSeconds: 1),

            $t->status_step < 2 => $this->step(2, fn () => $p->validateAndReserve($t), releaseSeconds: 1),

            $t->status_step < 3 => $this->step(3, fn () => $p->ensureServer($t), releaseSeconds: 60),

            $t->status_step === 3 => $this->pollServerReady($p, $t),

            $t->status_step < 5 => $this->step(5, fn () => $p->ensureDnsRecords($t), releaseSeconds: 1),

            $t->status_step < 6 => $this->step(6, fn () => $p->ensurePrimarySite($t), releaseSeconds: 1),

            $t->status_step < 7 => $this->step(7, fn () => $p->ensureGitRepo($t), releaseSeconds: 1),

            $t->status_step < 8 => $this->step(8, fn () => $p->ensureDatabase($t), releaseSeconds: 1),

            $t->status_step < 9 => $this->step(9, fn () => $p->writeEnvFile($t), releaseSeconds: 1),

            $t->status_step < 10 => $this->step(10, fn () => $p->requestPrimarySsl($t), releaseSeconds: 60),

            $t->status_step < 11 => $this->step(11, fn () => $p->ensureWebsocketSite($t), releaseSeconds: 60),

            $t->status_step < 12 => $this->step(12, function () use ($p, $t) {
                $p->ensureDeployScript($t);
                $t->recordStep(11, 'deploy complete');
            }, releaseSeconds: 1),

            $t->status_step < 14 => $this->step(14, fn () => $p->ensureDaemons($t), releaseSeconds: 1),

            $t->status_step < 15 => $this->step(15, fn () => $p->runPythonRecipe($t), releaseSeconds: 180),

            $t->status_step < 16 => $this->step(16, fn () => $p->runReverbRecipe($t), releaseSeconds: 60),

            $t->status_step < 17 => $this->step(17, fn () => $p->enableScheduler($t), releaseSeconds: 1),

            $t->status_step < 18 => $this->step(18, fn () => $p->seedAdmin($t), releaseSeconds: 30),

            $t->status_step < 19 => $this->step(19, function () use ($t) {
                Mail::to($t->admin_email)->queue(new TenantCredentials($t));
                $t->recordStep(18, 'credentials email queued');
            }, releaseSeconds: 1),

            $t->status_step < 20 => $this->step(20, fn () => $p->notifyPartnerstack($t), releaseSeconds: 1),

            $t->status_step < 21 => $this->step(21, function () use ($t) {
                broadcast(new TenantReady($t));
                $t->recordStep(20, 'TenantReady broadcast');
            }, releaseSeconds: 1),

            $t->status_step < 22 => $p->markReady($t),

            default => null,
        };
    }

    /**
     * Run a single pipeline step, then bump status_step past the supplied
     * gate so the next iteration falls into the next branch. Re-queues the
     * job after $releaseSeconds.
     */
    private function step(int $advanceTo, \Closure $body, int $releaseSeconds): void
    {
        $body();

        if ($this->tenant->status_step < $advanceTo) {
            $this->tenant->status_step = $advanceTo;
            $this->tenant->save();
        }

        $this->release($releaseSeconds);
    }

    private function pollServerReady(TenantProvisioner $p, $t): void
    {
        if (! $p->serverIsReady($t)) {
            $this->release(60);

            return;
        }

        $t->recordStep(4, 'server ready');
        $this->release(1);
    }
}
