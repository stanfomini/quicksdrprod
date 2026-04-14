<?php

namespace App\Services\Provisioning;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP wrapper around the Cloudflare DNS API. Scoped to a single zone
 * (quicksdr.com) — one per orchestrator deployment.
 *
 * Docs: https://developers.cloudflare.com/api/operations/dns-records-for-a-zone-list-dns-records
 */
class CloudflareClient
{
    private string $token;

    private string $zoneId;

    public function __construct(?string $token = null, ?string $zoneId = null)
    {
        $token ??= config('services.cloudflare.token');
        $zoneId ??= config('services.cloudflare.zone_id');

        if (! $token || ! $zoneId) {
            throw new RuntimeException('Cloudflare credentials not configured (services.cloudflare.token + .zone_id).');
        }

        $this->token = $token;
        $this->zoneId = $zoneId;
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->timeout(15)
            ->baseUrl('https://api.cloudflare.com/client/v4');
    }

    /**
     * Find a DNS record by name (exact match).
     *
     * @return array<string, mixed>|null
     */
    public function findRecord(string $name, string $type = 'A'): ?array
    {
        $response = $this->client()->get("/zones/{$this->zoneId}/dns_records", [
            'name' => $name,
            'type' => $type,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Cloudflare find failed: {$response->body()}");
        }

        $records = $response->json('result', []);

        return $records[0] ?? null;
    }

    /**
     * Upsert a DNS A record. Returns the record's Cloudflare ID.
     */
    public function upsertARecord(string $name, string $ip, int $ttl = 1, bool $proxied = false): string
    {
        $existing = $this->findRecord($name, 'A');

        if ($existing) {
            $response = $this->client()->put("/zones/{$this->zoneId}/dns_records/{$existing['id']}", [
                'type' => 'A',
                'name' => $name,
                'content' => $ip,
                'ttl' => $ttl,
                'proxied' => $proxied,
            ]);
        } else {
            $response = $this->client()->post("/zones/{$this->zoneId}/dns_records", [
                'type' => 'A',
                'name' => $name,
                'content' => $ip,
                'ttl' => $ttl,
                'proxied' => $proxied,
            ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException("Cloudflare upsert failed: {$response->body()}");
        }

        return $response->json('result.id');
    }

    public function deleteRecord(string $recordId): void
    {
        $response = $this->client()->delete("/zones/{$this->zoneId}/dns_records/{$recordId}");

        if (! $response->successful() && $response->status() !== 404) {
            throw new RuntimeException("Cloudflare delete failed: {$response->body()}");
        }
    }
}
