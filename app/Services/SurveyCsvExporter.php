<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyCsvExporter
{
    /**
     * Stream a CSV of every response to the given survey, including each
     * answerable question as its own column. Used by the Filament Responses
     * relation manager and the native /admin/surveys/{id}/responses page.
     */
    public function streamDownload(Survey $survey): StreamedResponse
    {
        $survey->loadMissing('questions');

        $filename = 'survey-'.$survey->slug.'-responses.csv';

        return response()->streamDownload(function () use ($survey): void {
            $handle = fopen('php://output', 'w');

            $headers = ['response_id', 'email', 'started_at', 'completed_at', 'duration_seconds'];
            foreach ($survey->questions as $question) {
                if (! $question->type->isAnswerable()) {
                    continue;
                }
                $headers[] = 'q'.$question->id.': '.$question->label;
            }
            fputcsv($handle, $headers);

            SurveyResponse::query()
                ->with(['answers'])
                ->where('survey_id', $survey->id)
                ->orderBy('id')
                ->chunk(100, function ($responses) use ($handle, $survey): void {
                    foreach ($responses as $response) {
                        $row = [
                            $response->id,
                            $response->email,
                            optional($response->started_at)->toIso8601String(),
                            optional($response->completed_at)->toIso8601String(),
                            $response->durationSeconds(),
                        ];

                        $answers = $response->answers->keyBy('survey_question_id');

                        foreach ($survey->questions as $question) {
                            if (! $question->type->isAnswerable()) {
                                continue;
                            }
                            $value = $answers->get($question->id)?->value['answer'] ?? null;
                            $row[] = is_array($value) ? implode('|', array_map('strval', $value)) : (string) ($value ?? '');
                        }

                        fputcsv($handle, $row);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
