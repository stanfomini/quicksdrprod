<?php

namespace App\Services;

use App\Jobs\SendLeadWebhookJob;

class LeadWebhookDispatcher
{
    /**
     * Fire the lead payload at every configured destination URL. Fan-out
     * is async via queued jobs so a slow/failing webhook never blocks the
     * user-facing form submit.
     *
     * Each URL in LEAD_WEBHOOK_URLS may optionally embed a secret after a
     * pipe `|`:
     *
     *     LEAD_WEBHOOK_URLS="https://a.example.com/hook|SECRET_A,https://b.example.com/hook"
     *
     * Entries without `|` POST plain JSON; entries with `|` also send an
     * X-Webhook-Secret header (matches what the quick-claimstaker lead
     * intake endpoint validates via hash_equals).
     *
     * @param  array<string, mixed>  $lead
     */
    public function dispatch(array $lead): void
    {
        $entries = config('services.lead_webhooks.urls', []);

        if (empty($entries)) {
            return;
        }

        $payload = array_merge([
            'source' => 'quicksdr.com',
            'submitted_at' => now()->toIso8601String(),
        ], $lead);

        foreach ($entries as $entry) {
            [$url, $secret] = $this->parseEntry($entry);

            if (! $url) {
                continue;
            }

            SendLeadWebhookJob::dispatch($url, $payload, $secret)->onQueue('webhooks');
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function parseEntry(string $entry): array
    {
        $entry = trim($entry);

        if ($entry === '') {
            return [null, null];
        }

        if (! str_contains($entry, '|')) {
            return [$entry, null];
        }

        [$url, $secret] = array_pad(explode('|', $entry, 2), 2, null);

        return [
            $url !== null ? trim($url) : null,
            $secret !== null ? trim($secret) : null,
        ];
    }
}
