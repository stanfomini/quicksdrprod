<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLeadWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $url,
        public array $payload,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->post($this->url, $this->payload);

            if (! $response->successful()) {
                Log::warning('LeadWebhook non-2xx', [
                    'url' => $this->url,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff()[$this->attempts() - 1] ?? 300);
                }
            }
        } catch (Throwable $e) {
            Log::warning('LeadWebhook exception', [
                'url' => $this->url,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('LeadWebhook failed after all retries', [
            'url' => $this->url,
            'error' => $e->getMessage(),
            'payload_keys' => array_keys($this->payload),
        ]);
    }
}
