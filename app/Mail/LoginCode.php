<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginCode extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
        public int $expiresInMinutes = 5,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your QuickSDR login code: {$this->code}",
            to: [$this->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.login-code',
            with: [
                'code' => $this->code,
                'minutes' => $this->expiresInMinutes,
                'name' => $this->user->name,
            ],
        );
    }
}
