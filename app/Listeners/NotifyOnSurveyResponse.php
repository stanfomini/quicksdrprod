<?php

namespace App\Listeners;

use App\Events\SurveyResponseSubmitted;
use App\Mail\SurveyResponseReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotifyOnSurveyResponse implements ShouldQueue
{
    public function handle(SurveyResponseSubmitted $event): void
    {
        $response = $event->response->fresh(['answers.question', 'survey']);
        if ($response === null) {
            return;
        }

        $survey = $response->survey;

        if (! empty($survey->email_notification_to)) {
            try {
                Mail::to($survey->email_notification_to)
                    ->send(new SurveyResponseReceived($response));
            } catch (Throwable $e) {
                Log::warning('survey: failed to send admin notification', [
                    'survey_id' => $survey->id,
                    'response_id' => $response->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($survey->notify_respondent && ! empty($response->email)) {
            try {
                Mail::to($response->email)
                    ->send((new SurveyResponseReceived($response))->forRespondent());
            } catch (Throwable $e) {
                Log::warning('survey: failed to email respondent', [
                    'response_id' => $response->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! empty($survey->webhook_url)) {
            try {
                Http::timeout(10)
                    ->retry(2, 500, throw: false)
                    ->post($survey->webhook_url, [
                        'event' => 'survey_response_submitted',
                        'survey' => [
                            'id' => $survey->id,
                            'slug' => $survey->slug,
                            'title' => $survey->title,
                        ],
                        'response' => [
                            'id' => $response->id,
                            'email' => $response->email,
                            'completed_at' => $response->completed_at?->toIso8601String(),
                            'duration_seconds' => $response->durationSeconds(),
                            'answers' => $response->answers->map(fn ($a) => [
                                'question_id' => $a->survey_question_id,
                                'question_label' => $a->question?->label,
                                'value' => $a->value['answer'] ?? null,
                            ])->all(),
                        ],
                    ]);
            } catch (Throwable $e) {
                Log::warning('survey: webhook failed', [
                    'survey_id' => $survey->id,
                    'response_id' => $response->id,
                    'webhook_url' => $survey->webhook_url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
