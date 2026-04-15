<?php

namespace App\Services\Provisioning;

use Illuminate\Support\Facades\Log;

/**
 * Non-network Cloudflare client used when PROVISIONING_DRY_RUN=true. Logs
 * every call and returns stable stub IDs so the real provisioner can record
 * them on the Tenant row and continue through later steps.
 */
class FakeCloudflareClient extends CloudflareClient
{
    public function __construct()
    {
        // Bypass parent: no token required in dry-run.
    }

    public function findRecord(string $name, string $type = 'A'): ?array
    {
        Log::info('[dry-run Cloudflare] findRecord', compact('name', 'type'));

        return null;
    }

    public function upsertARecord(string $name, string $ip, int $ttl = 1, bool $proxied = false): string
    {
        $id = 'cf_'.substr(md5("{$name}:{$ip}"), 0, 16);
        Log::info('[dry-run Cloudflare] upsertARecord', compact('name', 'ip', 'ttl', 'proxied', 'id'));

        return $id;
    }

    public function deleteRecord(string $recordId): void
    {
        Log::info('[dry-run Cloudflare] deleteRecord', compact('recordId'));
    }
}
