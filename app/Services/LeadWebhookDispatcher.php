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
     * @param  array<string, mixed>  $lead
     */
    public function dispatch(array $lead): void
    {
        $urls = config('services.lead_webhooks.urls', []);

        if (empty($urls)) {
            return;
        }

        $payload = array_merge([
            'source' => 'quicksdr.com',
            'submitted_at' => now()->toIso8601String(),
        ], $lead);

        foreach ($urls as $url) {
            SendLeadWebhookJob::dispatch($url, $payload)->onQueue('webhooks');
        }
    }
}
