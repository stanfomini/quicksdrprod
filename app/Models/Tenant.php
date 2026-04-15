<?php

namespace App\Models;

use App\Enums\TenantStatus;
use App\Enums\TenantTier;
use App\Events\TenantStatusChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tier' => TenantTier::class,
            'dry_run' => 'boolean',
            'status' => TenantStatus::class,
            'status_step' => 'int',
            'status_detail' => 'array',
            'forge_server_id' => 'int',
            'forge_site_id' => 'int',
            'forge_ws_site_id' => 'int',
            'forge_database_id' => 'int',
            'app_key' => 'encrypted',
            'reverb_app_id' => 'encrypted',
            'reverb_app_key' => 'encrypted',
            'reverb_app_secret' => 'encrypted',
            'stats_hmac_key' => 'encrypted',
            'db_password' => 'encrypted',
            'admin_signup_url' => 'encrypted',
            'provisioned_at' => 'datetime',
            'export_only_until' => 'datetime',
            'destroyed_at' => 'datetime',
        ];
    }

    public function stats(): HasMany
    {
        return $this->hasMany(TenantStat::class);
    }

    public function latestStat(): ?TenantStat
    {
        return $this->stats()->latest('period_start')->first();
    }

    public function primaryHost(): string
    {
        return "{$this->subdomain}.quicksdr.com";
    }

    public function websocketHost(): string
    {
        return "ws.{$this->subdomain}.quicksdr.com";
    }

    public function appUrl(): string
    {
        return "https://{$this->primaryHost()}";
    }

    public function isExportOnlyExpired(): bool
    {
        return $this->status === TenantStatus::ExportOnly
            && $this->export_only_until !== null
            && $this->export_only_until->isPast();
    }

    /**
     * Record a step transition with a message.
     */
    public function recordStep(int $step, string $message, array $extra = []): void
    {
        $log = $this->status_detail['log'] ?? [];
        $log[] = array_merge([
            'step' => $step,
            'at' => now()->toIso8601String(),
            'message' => $message,
        ], $extra);

        $this->status_step = $step;
        $this->status_detail = [
            ...($this->status_detail ?? []),
            'log' => array_slice($log, -100), // keep last 100 entries
        ];
        $this->save();

        // Fire-and-forget broadcast so the admin UI can tick forward live
        // during the 10+ minute provisioning pipeline. Ignore any failure —
        // broadcasting is best-effort and must not block pipeline progress.
        try {
            broadcast(new TenantStatusChanged($this, $step, $message));
        } catch (\Throwable) {
            // swallow
        }
    }

    public function markFailed(int $step, string $error): void
    {
        $this->status = TenantStatus::Failed;
        $this->recordStep($step, "failed: {$error}", ['error' => $error]);
    }

    /**
     * Return a reserved-subdomain deny list. Kept on the model so it's easy to
     * reference from form validation + tests.
     *
     * @return array<int, string>
     */
    public static function reservedSubdomains(): array
    {
        return [
            'www', 'api', 'admin', 'app', 'ws', 'stream',
            'staging', 'demo', 'support', 'help', 'blog',
            'mail', 'docs', 'status', 'billing', 'auth',
            'stripe', 'webhook', 'cdn', 'static', 'assets',
        ];
    }

    public static function isReservedSubdomain(string $subdomain): bool
    {
        return in_array(Str::lower($subdomain), self::reservedSubdomains(), true);
    }
}
