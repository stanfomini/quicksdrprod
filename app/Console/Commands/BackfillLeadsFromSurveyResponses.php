<?php

namespace App\Console\Commands;

use App\Enums\CrmField;
use App\Models\Lead;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('surveys:backfill-leads {--dry-run : Report what would happen without writing anything}')]
#[Description('Walk every completed SurveyResponse, extract CRM fields, and create or link Leads.')]
class BackfillLeadsFromSurveyResponses extends Command
{
    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('Dry-run mode — no rows will be written.');
        }

        $created = 0;
        $updated = 0;
        $linkedResponses = 0;
        $linkedTenants = 0;
        $skipped = 0;

        SurveyResponse::query()
            ->whereNotNull('completed_at')
            ->with(['survey', 'answers.question'])
            ->orderBy('completed_at')
            ->chunk(100, function ($responses) use (&$created, &$updated, &$linkedResponses, &$linkedTenants, &$skipped, $dry) {
                foreach ($responses as $response) {
                    $fields = $this->extractCrmFields($response->answers);

                    if (empty($fields['email']) && empty($fields['phone'])) {
                        $skipped++;

                        continue;
                    }

                    $fields['source'] = 'survey:'.$response->survey->slug;

                    if ($dry) {
                        $existing = $this->lookup($fields);
                        if ($existing) {
                            $updated++;
                        } else {
                            $created++;
                        }
                        $linkedResponses++;

                        continue;
                    }

                    $existing = $this->lookup($fields);
                    $lead = Lead::findOrCreateMatching($fields);

                    if ($existing === null) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    $beforeTenantId = $lead->tenant_id;
                    $lead->linkToTenantIfPossible();
                    if ($beforeTenantId === null && $lead->tenant_id !== null) {
                        $linkedTenants++;
                    }

                    if ($response->lead_id !== $lead->id) {
                        $response->lead_id = $lead->id;
                        $response->saveQuietly();
                    }

                    $linkedResponses++;
                }
            });

        $this->newLine();
        $this->info('Backfill summary');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Leads created', $created],
                ['Leads updated', $updated],
                ['Responses linked to a lead', $linkedResponses],
                ['Leads newly linked to a tenant', $linkedTenants],
                ['Responses skipped (no email or phone)', $skipped],
            ],
        );

        return self::SUCCESS;
    }

    /**
     * @param  array{email?: ?string, phone?: ?string}  $fields
     */
    protected function lookup(array $fields): ?Lead
    {
        $email = Lead::normalizeEmail($fields['email'] ?? null);
        $phone = Lead::normalizePhone($fields['phone'] ?? null);

        if ($email !== null) {
            $lead = Lead::query()->where('email', $email)->first();
            if ($lead) {
                return $lead;
            }
        }
        if ($phone !== null) {
            return Lead::query()->where('phone', $phone)->first();
        }

        return null;
    }

    /**
     * @param  iterable<int, SurveyAnswer>  $answers
     * @return array{email: ?string, phone: ?string, name: ?string, company: ?string}
     */
    protected function extractCrmFields(iterable $answers): array
    {
        $out = ['email' => null, 'phone' => null, 'name' => null, 'company' => null];

        foreach ($answers as $answer) {
            $question = $answer->question;
            if ($question === null) {
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
