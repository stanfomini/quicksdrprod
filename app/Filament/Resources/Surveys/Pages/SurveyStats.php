<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Enums\SurveyQuestionType;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SurveyStats extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SurveyResource::class;

    protected string $view = 'filament.resources.surveys.pages.survey-stats';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Stats — '.$this->getRecord()->title;
    }

    public function getBreadcrumb(): string
    {
        return 'Stats';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to survey')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => SurveyResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $surveyId = $this->getRecord()->id;

        $responsesQuery = SurveyResponse::query()->where('survey_id', $surveyId);

        $total = (clone $responsesQuery)->count();
        $completed = (clone $responsesQuery)->whereNotNull('completed_at')->count();
        $rate = $total > 0 ? round(($completed / $total) * 100) : 0;

        $avgSeconds = (clone $responsesQuery)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get()
            ->avg(fn (SurveyResponse $r) => $r->durationSeconds() ?? 0);

        $last7days = (clone $responsesQuery)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(6)->startOfDay())
            ->get()
            ->groupBy(fn (SurveyResponse $r) => $r->completed_at->format('Y-m-d'));

        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chart[$date] = $last7days->get($date)?->count() ?? 0;
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'completionRate' => $rate,
            'avgDuration' => $this->formatSeconds((int) $avgSeconds),
            'chart' => $chart,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getQuestionBreakdowns(): array
    {
        $output = [];

        foreach ($this->getRecord()->questions as $question) {
            if (! in_array($question->type, [SurveyQuestionType::Radio, SurveyQuestionType::Select, SurveyQuestionType::YesNo, SurveyQuestionType::Checkbox], true)) {
                continue;
            }

            $answers = SurveyAnswer::query()
                ->where('survey_question_id', $question->id)
                ->whereHas('response', fn ($q) => $q->whereNotNull('completed_at'))
                ->get();

            $counts = [];
            foreach ($answers as $answer) {
                $value = $answer->value['answer'] ?? null;
                $values = is_array($value) ? $value : [$value];

                foreach ($values as $v) {
                    if ($v === null || $v === '') {
                        continue;
                    }
                    $counts[(string) $v] = ($counts[(string) $v] ?? 0) + 1;
                }
            }

            arsort($counts);
            $total = array_sum($counts);

            $output[] = [
                'question' => $question,
                'counts' => $counts,
                'total' => $total,
            ];
        }

        return $output;
    }

    protected function formatSeconds(int $seconds): string
    {
        if ($seconds <= 0) {
            return '—';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        if ($minutes === 0) {
            return $seconds.'s';
        }

        return $minutes.'m '.$remaining.'s';
    }
}
