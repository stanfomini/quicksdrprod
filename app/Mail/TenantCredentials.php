<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantCredentials extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your QuickSDR is ready — {$this->tenant->primaryHost()}",
            to: [$this->tenant->admin_email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant-credentials',
            with: [
                'tenant' => $this->tenant,
                'loginUrl' => $this->tenant->admin_signup_url ?? $this->tenant->appUrl().'/login',
                'appUrl' => $this->tenant->appUrl(),
                'wsUrl' => "https://{$this->tenant->websocketHost()}",
            ],
        );
    }
}
