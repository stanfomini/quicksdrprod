<x-filament-panels::page>
    @php
        $statistics = $this->getStatistics();
        $summary = $statistics->summary();
        $timeSeries = $statistics->timeSeries(30);
        $dropOff = $statistics->dropOff();
        $analyses = $statistics->questionAnalyses();
    @endphp

    @php
        $formatDuration = function (?float $seconds): string {
            if ($seconds === null || $seconds <= 0) return '—';
            $s = (int) round($seconds);
            $m = intdiv($s, 60);
            $r = $s % 60;
            return $m > 0 ? $m.'m '.$r.'s' : $s.'s';
        };
    @endphp

    {{-- =================== TOP-LINE SUMMARY =================== --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <x-filament::section>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Total responses</div>
            <div class="mt-1 text-3xl font-bold">{{ $summary['total_responses'] }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $summary['in_progress'] }} in progress</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Completed</div>
            <div class="mt-1 text-3xl font-bold">{{ $summary['completed'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Completion rate</div>
            <div class="mt-1 text-3xl font-bold">{{ $summary['completion_rate'] }}%</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Avg duration</div>
            <div class="mt-1 text-3xl font-bold">{{ $formatDuration($summary['avg_duration_seconds']) }}</div>
            <div class="text-xs text-gray-400 mt-1">median {{ $formatDuration($summary['median_duration_seconds']) }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Latest</div>
            <div class="mt-1 text-sm font-medium">
                @if ($summary['last_response_at'])
                    {{ \Carbon\Carbon::parse($summary['last_response_at'])->diffForHumans() }}
                @else
                    —
                @endif
            </div>
            @if ($summary['first_response_at'])
                <div class="text-xs text-gray-400 mt-1">
                    first {{ \Carbon\Carbon::parse($summary['first_response_at'])->format('M j') }}
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- =================== EMPTY STATE =================== --}}
    @if ($summary['completed'] === 0)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="text-4xl mb-4">📊</div>
                <div class="text-lg font-medium">No completed responses yet.</div>
                <div class="text-sm text-gray-500 mt-2">Stats will populate as people submit the survey.</div>
            </div>
        </x-filament::section>
    @else

        {{-- =================== 30-DAY TIME SERIES =================== --}}
        <x-filament::section>
            <x-slot name="heading">Responses — last 30 days</x-slot>

            @php($maxBar = max(array_merge([1], $timeSeries)))
            <div class="flex items-end gap-1 h-32 mt-4">
                @foreach ($timeSeries as $date => $count)
                    @php($pct = ($count / $maxBar) * 100)
                    <div class="flex-1 flex flex-col items-center justify-end gap-1 group" title="{{ $date }}: {{ $count }}">
                        <div class="text-[10px] text-gray-400 opacity-0 group-hover:opacity-100 transition">{{ $count }}</div>
                        <div
                            class="w-full bg-primary-500 rounded-t hover:bg-primary-600 transition"
                            style="height: {{ $pct }}%; min-height: {{ $count > 0 ? '3px' : '0' }};"
                        ></div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-2">
                <span>{{ \Carbon\Carbon::parse(array_key_first($timeSeries))->format('M j') }}</span>
                <span>Today</span>
            </div>
        </x-filament::section>

        {{-- =================== DROP-OFF FUNNEL =================== --}}
        @if (count($dropOff) > 0)
            <x-filament::section>
                <x-slot name="heading">Question drop-off</x-slot>
                <x-slot name="description">Where respondents skip — based on {{ $summary['completed'] }} completed responses.</x-slot>

                <div class="space-y-2">
                    @foreach ($dropOff as $row)
                        @php($answeredPct = $summary['completed'] > 0 ? round(($row['answered'] / $summary['completed']) * 100, 1) : 0)
                        <div class="flex items-center gap-3">
                            <div class="w-1/3 text-sm truncate" title="{{ $row['question']->label }}">
                                {{ $row['question']->label }}
                            </div>
                            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-4 overflow-hidden relative">
                                <div class="bg-primary-500 h-full rounded-full" style="width: {{ $answeredPct }}%"></div>
                            </div>
                            <div class="w-44 text-right text-sm text-gray-600 dark:text-gray-400">
                                {{ $row['answered'] }} answered
                                @if ($row['skipped'] > 0)
                                    <span class="text-red-500">· {{ $row['skipped'] }} skipped ({{ $row['skip_rate'] }}%)</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- =================== PER-QUESTION ANALYSIS =================== --}}
        <div class="space-y-6">
            <h2 class="text-lg font-semibold mt-4">Question breakdown</h2>

            @foreach ($analyses as $i => $a)
                @if ($a['kind'] === 'layout')
                    <div class="border-l-4 border-gray-300 dark:border-gray-700 pl-4 py-1">
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ $a['question']->type->label() }}</div>
                        <div class="font-medium">{{ $a['question']->label }}</div>
                    </div>
                    @continue
                @endif

                @php($q = $a['question'])

                <x-filament::section>
                    <x-slot name="heading">
                        <span class="text-sm text-gray-400 mr-2">Q{{ $i + 1 }}.</span>{{ $q->label }}
                    </x-slot>

                    <x-slot name="description">
                        <div class="flex items-center gap-3 text-xs">
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800">{{ $q->type->label() }}</span>
                            <span>n = <span class="font-mono">{{ $a['n'] }}</span></span>
                            @if (($a['skipped'] ?? 0) > 0)
                                <span class="text-amber-600">{{ $a['skipped'] }} skipped ({{ $a['skip_rate'] }}%)</span>
                            @endif
                        </div>
                    </x-slot>

                    @if ($a['n'] === 0)
                        <div class="text-sm text-gray-500 italic">No data yet.</div>
                    @else

                        {{-- ===== CHOICE (radio/select/yes_no) ===== --}}
                        @if ($a['kind'] === 'choice')
                            <div class="space-y-2">
                                @foreach ($a['rows'] as $row)
                                    <div class="flex items-center gap-3">
                                        <div class="w-1/3 text-sm truncate flex items-center gap-2">
                                            @if ($a['mode'] === $row['value'] && $row['count'] > 0)
                                                <span class="text-amber-500" title="Mode (most common)">★</span>
                                            @endif
                                            {{ $row['label'] }}
                                        </div>
                                        <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-5 overflow-hidden relative">
                                            <div class="bg-primary-500 h-full" style="width: {{ $row['percent'] }}%"></div>
                                        </div>
                                        <div class="w-32 text-right text-sm font-mono">
                                            {{ $row['count'] }} <span class="text-gray-500">({{ $row['percent'] }}%)</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- ===== MULTI-CHOICE (checkbox) ===== --}}
                        @if ($a['kind'] === 'multi_choice')
                            <div class="text-xs text-gray-500 mb-3">
                                Avg selections per respondent: <strong>{{ $a['avg_selections'] }}</strong>
                                · Respondents: <strong>{{ $a['total_respondents'] }}</strong>
                                <span class="ml-2 italic">(percentages relative to respondents — sum can exceed 100%)</span>
                            </div>
                            <div class="space-y-2">
                                @foreach ($a['rows'] as $row)
                                    <div class="flex items-center gap-3">
                                        <div class="w-1/3 text-sm truncate">{{ $row['label'] }}</div>
                                        <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-5 overflow-hidden">
                                            <div class="bg-primary-500 h-full" style="width: {{ min($row['percent'], 100) }}%"></div>
                                        </div>
                                        <div class="w-32 text-right text-sm font-mono">
                                            {{ $row['count'] }} <span class="text-gray-500">({{ $row['percent'] }}%)</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- ===== NUMERIC (scale/rating/number) ===== --}}
                        @if ($a['kind'] === 'numeric' && ($a['numbers'] ?? 0) > 0)
                            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4">
                                <div><div class="text-xs text-gray-500">Mean</div><div class="font-mono text-lg">{{ $a['mean'] }}</div></div>
                                <div><div class="text-xs text-gray-500">Median</div><div class="font-mono text-lg">{{ $a['median'] }}</div></div>
                                <div><div class="text-xs text-gray-500">Mode</div><div class="font-mono text-lg">{{ $a['mode'] }}</div></div>
                                <div><div class="text-xs text-gray-500">Std dev</div><div class="font-mono text-lg">{{ $a['std_dev'] }}</div></div>
                                <div><div class="text-xs text-gray-500">Min</div><div class="font-mono text-lg">{{ $a['min'] }}</div></div>
                                <div><div class="text-xs text-gray-500">Max</div><div class="font-mono text-lg">{{ $a['max'] }}</div></div>
                            </div>
                            @if (! empty($a['distribution']))
                                <div class="text-xs text-gray-500 mb-2 mt-3">Distribution</div>
                                <div class="space-y-1">
                                    @foreach ($a['distribution'] as $bin)
                                        <div class="flex items-center gap-3">
                                            <div class="w-20 text-sm font-mono text-right">{{ $bin['label'] }}</div>
                                            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded h-5 overflow-hidden">
                                                <div class="bg-primary-500 h-full" style="width: {{ $bin['percent'] }}%"></div>
                                            </div>
                                            <div class="w-32 text-right text-sm font-mono">
                                                {{ $bin['count'] }} <span class="text-gray-500">({{ $bin['percent'] }}%)</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        {{-- ===== EMAIL ===== --}}
                        @if ($a['kind'] === 'email')
                            @if (! empty($a['domains']))
                                <div class="text-xs text-gray-500 mb-2">Domain breakdown</div>
                                <div class="space-y-2 mb-4">
                                    @foreach ($a['domains'] as $row)
                                        <div class="flex items-center gap-3">
                                            <div class="w-1/3 text-sm font-mono truncate">{{ $row['label'] }}</div>
                                            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-5 overflow-hidden">
                                                <div class="bg-primary-500 h-full" style="width: {{ $row['percent'] }}%"></div>
                                            </div>
                                            <div class="w-32 text-right text-sm font-mono">
                                                {{ $row['count'] }} <span class="text-gray-500">({{ $row['percent'] }}%)</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (! empty($a['sample']))
                                <div class="text-xs text-gray-500 mb-2 mt-4">Sample</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($a['sample'] as $email)
                                        <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded font-mono">{{ $email }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        {{-- ===== TEXT (text/textarea/name/url/phone) ===== --}}
                        @if ($a['kind'] === 'text')
                            <div class="text-xs text-gray-500 mb-3">
                                Avg length: <strong>{{ $a['avg_length'] }}</strong> chars
                            </div>

                            @if (! empty($a['top_words']))
                                <div class="text-xs text-gray-500 mb-2">Top words</div>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach ($a['top_words'] as $word => $count)
                                        @php($size = min(1.6, 0.85 + ($count / max(reset($a['top_words']), 1)) * 0.7))
                                        <span class="px-2 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-800 dark:text-primary-200 rounded"
                                              style="font-size: {{ $size }}rem;">
                                            {{ $word }} <span class="text-xs opacity-60">{{ $count }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if (! empty($a['sample']))
                                <div class="text-xs text-gray-500 mb-2 mt-4">Sample responses</div>
                                <div class="space-y-2">
                                    @foreach ($a['sample'] as $s)
                                        <div class="text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded px-3 py-2">
                                            {{ \Illuminate\Support\Str::limit($s, 200) }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        {{-- ===== DATE / TIME ===== --}}
                        @if ($a['kind'] === 'date')
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div><div class="text-xs text-gray-500">Earliest</div><div class="font-mono">{{ $a['min'] ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-500">Latest</div><div class="font-mono">{{ $a['max'] ?? '—' }}</div></div>
                            </div>
                            @if (! empty($a['sample']))
                                <div class="text-xs text-gray-500 mb-2">Sample</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($a['sample'] as $stamp)
                                        <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded font-mono">{{ $stamp }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        {{-- ===== FILE ===== --}}
                        @if ($a['kind'] === 'file')
                            <div class="text-sm">
                                <strong>{{ $a['count'] }}</strong> uploads
                            </div>
                            @if (! empty($a['paths']))
                                <ul class="text-xs text-gray-500 mt-2 space-y-1">
                                    @foreach ($a['paths'] as $p)
                                        <li class="font-mono truncate">{{ $p }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        @endif

                    @endif
                </x-filament::section>
            @endforeach
        </div>

    @endif
</x-filament-panels::page>
