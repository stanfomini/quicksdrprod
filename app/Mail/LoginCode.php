<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent synchronously (NOT queued via ShouldQueue) so the OTP code lands
 * in the user's inbox before the /email-code page finishes rendering.
 * Queue lag of even a couple seconds is unacceptable UX for 2FA.
 */
class LoginCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
        public int $expiresInMinutes = 5,
    ) {}

    public function envelope(): Envelope
    {
        // Subject deliberately omits the code — Gmail flags emails with
        // numeric/code-looking content in the subject as Promotions, which
        // hides the OTP under a separate inbox tab. The code is in the body.
        return new Envelope(
            subject: 'QuickSDR sign-in verification',
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
