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

class PaymentFailed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public int $attemptCount = 1,
        public ?Carbon $nextAttemptAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Action required: payment failed for {$this->tenant->primaryHost()}",
            to: [$this->tenant->admin_email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-failed',
            with: [
                'tenant' => $this->tenant,
                'attemptCount' => $this->attemptCount,
                'nextAttemptAt' => $this->nextAttemptAt,
                'billingUrl' => $this->tenant->appUrl().'/settings/billing',
            ],
        );
    }
}
