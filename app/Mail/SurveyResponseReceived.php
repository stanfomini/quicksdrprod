<?php

namespace App\Mail;

use App\Models\SurveyResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyResponseReceived extends Mailable
{
    use Queueable, SerializesModels;

    public bool $forRespondent = false;

    public function __construct(public SurveyResponse $response) {}

    public function forRespondent(): self
    {
        $this->forRespondent = true;

        return $this;
    }

    public function envelope(): Envelope
    {
        $survey = $this->response->survey;

        return new Envelope(
            subject: $this->forRespondent
                ? 'We received your response — '.$survey->title
                : 'New response: '.$survey->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.survey-response-received',
            with: [
                'response' => $this->response,
                'survey' => $this->response->survey,
                'forRespondent' => $this->forRespondent,
            ],
        );
    }
}
