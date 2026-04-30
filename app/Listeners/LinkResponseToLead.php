<?php

namespace App\Listeners;

use App\Enums\CrmField;
use App\Events\SurveyResponseSubmitted;
use App\Models\Lead;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use Illuminate\Support\Facades\Log;
use Throwable;

class LinkResponseToLead
{
    public function handle(SurveyResponseSubmitted $event): void
    {
        try {
            $response = $event->response->fresh(['answers.question', 'survey']);
            if ($response === null) {
                return;
            }

            $fields = $this->extractCrmFields($response->answers);

            // Need at least an email or phone to bother creating a Lead.
            if (empty($fields['email']) && empty($fields['phone'])) {
                return;
            }

            $fields['source'] = $response->source ?? ('survey:'.$response->survey->slug);

            $lead = Lead::findOrCreateMatching($fields);
            $lead->linkToTenantIfPossible();

            $response->lead_id = $lead->id;
            $response->saveQuietly();
        } catch (Throwable $e) {
            Log::warning('survey: failed to link response to lead', [
                'response_id' => $event->response->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Walk a response's answers and build a flat CRM field array.
     * crm_field on the question takes precedence over inferring from question type.
     *
     * @param  iterable<int, SurveyAnswer>  $answers
     * @return array{email: ?string, phone: ?string, name: ?string, company: ?string}
     */
    protected function extractCrmFields(iterable $answers): array
    {
        $out = ['email' => null, 'phone' => null, 'name' => null, 'company' => null];

        foreach ($answers as $answer) {
            $question = $answer->question;
            if (! $question instanceof SurveyQuestion) {
                continue;
            }

            $value = $answer->value['answer'] ?? null;
            if (! is_string($value) || $value === '') {
                continue;
            }

            $field = $question->crm_field instanceof CrmField
                ? $question->crm_field
                : CrmField::defaultForType($question->type);

            $out = match ($field) {
                CrmField::Email => [...$out, 'email' => $out['email'] ?? $value],
                CrmField::Phone => [...$out, 'phone' => $out['phone'] ?? $value],
                CrmField::Name => [...$out, 'name' => $out['name'] ?? $value],
                CrmField::Company => [...$out, 'company' => $out['company'] ?? $value],
                CrmField::None => $out,
            };
        }

        return $out;
    }
}
