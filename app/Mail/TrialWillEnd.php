<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class TrialWillEnd extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant, public ?Carbon $trialEndsAt = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your QuickSDR trial ends soon — {$this->tenant->primaryHost()}",
            to: [$this->tenant->admin_email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.trial-will-end',
            with: [
                'tenant' => $this->tenant,
                'trialEndsAt' => $this->trialEndsAt,
                'billingUrl' => $this->tenant->appUrl().'/settings/billing',
            ],
        );
    }
}
