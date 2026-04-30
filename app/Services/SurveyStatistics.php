<?php

namespace App\Services;

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class SurveyStatistics
{
    public function __construct(public Survey $survey)
    {
        $this->survey->loadMissing('questions');
    }

    /**
     * @return array{
     *     total_responses: int,
     *     completed: int,
     *     in_progress: int,
     *     completion_rate: float,
     *     avg_duration_seconds: ?float,
     *     median_duration_seconds: ?float,
     *     first_response_at: ?string,
     *     last_response_at: ?string
     * }
     */
    public function summary(): array
    {
        $responses = SurveyResponse::query()
            ->where('survey_id', $this->survey->id)
            ->get();

        $completed = $responses->whereNotNull('completed_at');
        $durations = $completed
            ->map(fn (SurveyResponse $r) => $r->durationSeconds())
            ->filter(fn (?int $d) => $d !== null && $d > 0)
            ->values();

        return [
            'total_responses' => $responses->count(),
            'completed' => $completed->count(),
            'in_progress' => $responses->whereNull('completed_at')->count(),
            'completion_rate' => $responses->count() > 0
                ? round(($completed->count() / $responses->count()) * 100, 1)
                : 0.0,
            'avg_duration_seconds' => $durations->isNotEmpty() ? round($durations->avg(), 1) : null,
            'median_duration_seconds' => $durations->isNotEmpty() ? $this->median($durations->all()) : null,
            'first_response_at' => $completed->min('completed_at')?->toIso8601String(),
            'last_response_at' => $completed->max('completed_at')?->toIso8601String(),
        ];
    }

    /**
     * Daily completion counts for the last N days.
     *
     * @return array<string, int>
     */
    public function timeSeries(int $days = 30): array
    {
        $rows = SurveyResponse::query()
            ->where('survey_id', $this->survey->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->get();

        $byDay = $rows->groupBy(fn ($r) => $r->completed_at->format('Y-m-d'));

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $series[$date] = $byDay->get($date)?->count() ?? 0;
        }

        return $series;
    }

    /**
     * Per-question response & skip counts. Useful for "where do people drop off?"
     *
     * @return array<int, array{question: SurveyQuestion, answered: int, skipped: int, skip_rate: float}>
     */
    public function dropOff(): array
    {
        $completedIds = SurveyResponse::query()
            ->where('survey_id', $this->survey->id)
            ->whereNotNull('completed_at')
            ->pluck('id');

        $totalCompleted = $completedIds->count();
        if ($totalCompleted === 0) {
            return [];
        }

        $output = [];
        foreach ($this->survey->questions as $question) {
            if (! $question->type->isAnswerable()) {
                continue;
            }

            $answered = SurveyAnswer::query()
                ->where('survey_question_id', $question->id)
                ->whereIn('survey_response_id', $completedIds)
                ->get()
                ->filter(function (SurveyAnswer $a): bool {
                    $v = $a->value['answer'] ?? null;

                    return $v !== null && $v !== '' && $v !== [];
                })
                ->count();

            $skipped = $totalCompleted - $answered;

            $output[] = [
                'question' => $question,
                'answered' => $answered,
                'skipped' => $skipped,
                'skip_rate' => $totalCompleted > 0 ? round(($skipped / $totalCompleted) * 100, 1) : 0.0,
            ];
        }

        return $output;
    }

    /**
     * Build the per-question analysis array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function questionAnalyses(): array
    {
        $completedIds = SurveyResponse::query()
            ->where('survey_id', $this->survey->id)
            ->whereNotNull('completed_at')
            ->pluck('id');

        $output = [];

        foreach ($this->survey->questions as $question) {
            if (! $question->type->isAnswerable()) {
                $output[] = ['kind' => 'layout', 'question' => $question];

                continue;
            }

            $answers = SurveyAnswer::query()
                ->where('survey_question_id', $question->id)
                ->whereIn('survey_response_id', $completedIds)
                ->get();

            $values = $answers
                ->map(fn (SurveyAnswer $a) => $a->value['answer'] ?? null)
                ->filter(fn ($v) => $v !== null && $v !== '' && $v !== []);

            $n = $values->count();
            $skipped = $completedIds->count() - $n;

            $analysis = match ($question->type) {
                SurveyQuestionType::Radio,
                SurveyQuestionType::Select,
                SurveyQuestionType::YesNo => $this->analyzeChoice($values, $question),

                SurveyQuestionType::Checkbox => $this->analyzeMultiChoice($values, $question),

                SurveyQuestionType::Scale,
                SurveyQuestionType::Rating,
                SurveyQuestionType::Number => $this->analyzeNumeric($values, $question),

                SurveyQuestionType::Email => $this->analyzeEmail($values),

                SurveyQuestionType::Date,
                SurveyQuestionType::DateTime,
                SurveyQuestionType::Time => $this->analyzeDate($values, $question->type),

                SurveyQuestionType::FileUpload => $this->analyzeFile($values),

                default => $this->analyzeText($values),
            };

            $output[] = array_merge([
                'question' => $question,
                'n' => $n,
                'skipped' => $skipped,
                'skip_rate' => $completedIds->count() > 0
                    ? round(($skipped / $completedIds->count()) * 100, 1)
                    : 0.0,
            ], $analysis);
        }

        return $output;
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return array<string, mixed>
     */
    protected function analyzeChoice(Collection $values, SurveyQuestion $question): array
    {
        $counts = [];
        foreach ($values as $v) {
            $key = (string) $v;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        if ($question->type !== SurveyQuestionType::YesNo) {
            foreach ($question->options['choices'] ?? [] as $choice) {
                $val = (string) ($choice['value'] ?? '');
                if ($val !== '' && ! isset($counts[$val])) {
                    $counts[$val] = 0;
                }
            }
        } else {
            $counts['yes'] ??= 0;
            $counts['no'] ??= 0;
        }

        arsort($counts);

        $total = max(array_sum($counts), 1);
        $rows = [];
        foreach ($counts as $value => $count) {
            $rows[] = [
                'value' => $value,
                'label' => $this->lookupLabel($question, $value),
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        }

        $mode = collect($rows)->where('count', '>', 0)->sortByDesc('count')->first()['value'] ?? null;

        return ['kind' => 'choice', 'rows' => $rows, 'mode' => $mode];
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return array<string, mixed>
     */
    protected function analyzeMultiChoice(Collection $values, SurveyQuestion $question): array
    {
        $counts = [];
        $selectionsPerResponse = [];

        foreach ($values as $v) {
            $arr = is_array($v) ? $v : [$v];
            $arr = array_values(array_filter($arr, fn ($x) => $x !== null && $x !== ''));
            $selectionsPerResponse[] = count($arr);

            foreach ($arr as $item) {
                $key = (string) $item;
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        foreach ($question->options['choices'] ?? [] as $choice) {
            $val = (string) ($choice['value'] ?? '');
            if ($val !== '' && ! isset($counts[$val])) {
                $counts[$val] = 0;
            }
        }

        arsort($counts);

        $totalRespondents = max(count($selectionsPerResponse), 1);
        $rows = [];
        foreach ($counts as $value => $count) {
            $rows[] = [
                'value' => $value,
                'label' => $this->lookupLabel($question, $value),
                'count' => $count,
                'percent' => round(($count / $totalRespondents) * 100, 1),
            ];
        }

        return [
            'kind' => 'multi_choice',
            'rows' => $rows,
            'avg_selections' => $selectionsPerResponse !== []
                ? round(array_sum($selectionsPerResponse) / count($selectionsPerResponse), 2)
                : 0.0,
            'total_respondents' => count($selectionsPerResponse),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return array<string, mixed>
     */
    protected function analyzeNumeric(Collection $values, SurveyQuestion $question): array
    {
        $numbers = $values
            ->map(fn ($v) => is_numeric($v) ? (float) $v : null)
            ->filter(fn ($v) => $v !== null)
            ->values()
            ->all();

        if ($numbers === []) {
            return ['kind' => 'numeric', 'numbers' => 0];
        }

        sort($numbers);
        $n = count($numbers);
        $mean = array_sum($numbers) / $n;
        $median = $this->median($numbers);
        $min = min($numbers);
        $max = max($numbers);
        $sd = $this->stdDev($numbers, $mean);

        $freq = [];
        foreach ($numbers as $num) {
            $key = (string) $num;
            $freq[$key] = ($freq[$key] ?? 0) + 1;
        }
        $maxFreq = max($freq);
        $modes = array_keys(array_filter($freq, fn ($c) => $c === $maxFreq));

        return [
            'kind' => 'numeric',
            'numbers' => $n,
            'mean' => round($mean, 2),
            'median' => $median,
            'mode' => count($modes) === 1 ? (string) $modes[0] : implode(', ', $modes),
            'std_dev' => round($sd, 2),
            'min' => $min,
            'max' => $max,
            'range' => round($max - $min, 2),
            'distribution' => $this->histogram($numbers, $question),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return array<string, mixed>
     */
    protected function analyzeEmail(Collection $values): array
    {
        $emails = $values->map(fn ($v) => (string) $v)->filter(fn ($v) => str_contains($v, '@'));
        $domains = [];

        foreach ($emails as $email) {
            $parts = explode('@', $email, 2);
            $domain = strtolower($parts[1] ?? '');
            if ($domain !== '') {
                $domains[$domain] = ($domains[$domain] ?? 0) + 1;
            }
        }

        arsort($domains);

        $total = max($emails->count(), 1);
        $rows = [];
        foreach ($domains as $domain => $count) {
            $rows[] = [
                'value' => $domain,
                'label' => $domain,
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        }

        return [
            'kind' => 'email',
            'domains' => $rows,
            'sample' => $emails->take(8)->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return array<string, mixed>
     */
    protected function analyzeDate(Collection $values, SurveyQuestionType $type): array
    {
        $stamps = $values
            ->map(function ($v) use ($type) {
                try {
                    return $type === SurveyQuestionType::Time
                        ? Carbon::createFromFormat('H:i', (string) $v)?->format('H:i')
                        : Carbon::parse((string) $v)?->toIso8601String();
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->values();

        return [
            'kind' => 'date',
            'min' => $stamps->min(),
            'max' => $stamps->max(),
            'sample' => $stamps->take(10)->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return array<string, mixed>
     */
    protected function analyzeText(Collection $values): array
    {
        $strings = $values->map(fn ($v) => (string) $v)->filter()->values();

        $lengths = $strings->map(fn (string $s) => mb_strlen($s));
        $avgLength = $lengths->isNotEmpty() ? round($lengths->avg(), 1) : 0.0;

        $stop = ['the', 'a', 'an', 'and', 'or', 'but', 'is', 'are', 'was', 'were',
            'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
            'i', 'me', 'my', 'we', 'our', 'you', 'your', 'he', 'she', 'it',
            'they', 'them', 'their', 'this', 'that', 'these', 'those',
            'to', 'of', 'in', 'on', 'at', 'by', 'for', 'with', 'as', 'from',
            'so', 'if', 'than', 'then', 'no', 'not', 'will', 'would', 'can',
            'could', 'should', 'just'];

        $wordCounts = [];
        foreach ($strings as $str) {
            $words = preg_split('/\W+/u', mb_strtolower($str)) ?: [];
            foreach ($words as $w) {
                if ($w === '' || mb_strlen($w) < 3 || in_array($w, $stop, true)) {
                    continue;
                }
                $wordCounts[$w] = ($wordCounts[$w] ?? 0) + 1;
            }
        }
        arsort($wordCounts);
        $topWords = array_slice($wordCounts, 0, 15, true);

        return [
            'kind' => 'text',
            'avg_length' => $avgLength,
            'top_words' => $topWords,
            'sample' => $strings->take(8)->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return array<string, mixed>
     */
    protected function analyzeFile(Collection $values): array
    {
        return [
            'kind' => 'file',
            'count' => $values->count(),
            'paths' => $values->take(10)->values()->all(),
        ];
    }

    protected function lookupLabel(SurveyQuestion $question, string $value): string
    {
        if ($question->type === SurveyQuestionType::YesNo) {
            return ucfirst($value);
        }

        foreach ($question->options['choices'] ?? [] as $choice) {
            if ((string) ($choice['value'] ?? '') === $value) {
                return (string) ($choice['label'] ?? $value);
            }
        }

        return $value;
    }

    /**
     * @param  array<int, float|int>  $numbers
     */
    protected function median(array $numbers): float|int
    {
        $count = count($numbers);
        sort($numbers);
        $mid = intdiv($count, 2);

        if ($count % 2 === 0) {
            return ($numbers[$mid - 1] + $numbers[$mid]) / 2;
        }

        return $numbers[$mid];
    }

    /**
     * @param  array<int, float|int>  $numbers
     */
    protected function stdDev(array $numbers, float $mean): float
    {
        $n = count($numbers);
        if ($n < 2) {
            return 0.0;
        }

        $sumSquares = 0.0;
        foreach ($numbers as $x) {
            $sumSquares += ($x - $mean) ** 2;
        }

        return sqrt($sumSquares / ($n - 1));
    }

    /**
     * @param  array<int, float|int>  $numbers
     * @return array<int, array{label: string, count: int, percent: float}>
     */
    protected function histogram(array $numbers, SurveyQuestion $question): array
    {
        $n = count($numbers);
        if ($n === 0) {
            return [];
        }

        if (in_array($question->type, [SurveyQuestionType::Scale, SurveyQuestionType::Rating], true)) {
            $max = (int) ($question->options['max'] ?? 5);
            $bins = [];
            for ($i = 1; $i <= $max; $i++) {
                $bins[$i] = 0;
            }
            foreach ($numbers as $num) {
                $key = (int) round($num);
                if (isset($bins[$key])) {
                    $bins[$key]++;
                }
            }

            $rows = [];
            foreach ($bins as $value => $count) {
                $rows[] = [
                    'label' => (string) $value,
                    'count' => $count,
                    'percent' => round(($count / $n) * 100, 1),
                ];
            }

            return $rows;
        }

        $min = min($numbers);
        $max = max($numbers);
        $range = max($max - $min, 1);
        $binWidth = $range / 10;

        $bins = array_fill(0, 10, 0);
        foreach ($numbers as $num) {
            $idx = min(9, (int) (($num - $min) / $binWidth));
            $bins[$idx]++;
        }

        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $low = $min + ($i * $binWidth);
            $high = $i === 9 ? $max : $low + $binWidth;
            $rows[] = [
                'label' => round($low, 1).'–'.round($high, 1),
                'count' => $bins[$i],
                'percent' => round(($bins[$i] / $n) * 100, 1),
            ];
        }

        return $rows;
    }
}
