<x-filament-panels::page>
    @php
        $statistics = $this->getStatistics();
        $summary = $statistics->summary();
        $timeSeries = $statistics->timeSeries(30);
        $dropOff = $statistics->dropOff();
        $analyses = $statistics->questionAnalyses();

        $formatDuration = function (?float $seconds): string {
            if ($seconds === null || $seconds <= 0) return '—';
            $s = (int) round($seconds);
            $m = intdiv($s, 60);
            $r = $s % 60;
            return $m > 0 ? $m.'m '.$r.'s' : $s.'s';
        };

        // SVG path for the time series area chart
        $tsValues = array_values($timeSeries);
        $tsMax = max(array_merge([1], $tsValues));
        $tsCount = count($tsValues);
        $tsPath = '';
        $tsArea = '';
        $w = 1000;
        $h = 140;
        $px = $w / max($tsCount - 1, 1);
        foreach ($tsValues as $i => $v) {
            $x = $i * $px;
            $y = $h - (($v / $tsMax) * ($h - 10)) - 5;
            $tsPath .= ($i === 0 ? 'M' : 'L').sprintf('%.2f,%.2f ', $x, $y);
        }
        $tsArea = $tsPath . sprintf('L%.2f,%.2f L0,%.2f Z', $w, $h, $h);

        $totalIn30 = array_sum($tsValues);
    @endphp

    {{-- =============================================================== --}}
    {{-- HERO KPI CARDS                                                   --}}
    {{-- =============================================================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total responses</div>
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/40 p-2 text-blue-600 dark:text-blue-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6a4 4 0 118 0v6m-4-10v.01M3 21h18"/></svg>
                </div>
            </div>
            <div class="mt-3 text-4xl font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format($summary['total_responses']) }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                @if ($summary['in_progress'] > 0)
                    <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $summary['in_progress'] }} in progress</span>
                @else
                    All submitted
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Completed</div>
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/40 p-2 text-emerald-600 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="mt-3 text-4xl font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format($summary['completed']) }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $summary['completion_rate'] }}% completion rate</div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Avg time</div>
                <div class="rounded-lg bg-violet-50 dark:bg-violet-950/40 p-2 text-violet-600 dark:text-violet-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3 text-4xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $formatDuration($summary['avg_duration_seconds']) }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">median {{ $formatDuration($summary['median_duration_seconds']) }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Latest</div>
                <div class="rounded-lg bg-amber-50 dark:bg-amber-950/40 p-2 text-amber-600 dark:text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">
                @if ($summary['last_response_at'])
                    {{ \Carbon\Carbon::parse($summary['last_response_at'])->diffForHumans(['short' => true]) }}
                @else
                    —
                @endif
            </div>
            @if ($summary['first_response_at'])
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">first {{ \Carbon\Carbon::parse($summary['first_response_at'])->format('M j, Y') }}</div>
            @endif
        </div>
    </div>

    {{-- =============================================================== --}}
    {{-- EMPTY STATE                                                      --}}
    {{-- =============================================================== --}}
    @if ($summary['completed'] === 0)
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-12 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">No completed responses yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Stats will populate as people submit the survey.</p>
        </div>
    @else

        {{-- =============================================================== --}}
        {{-- TIME SERIES — area chart                                        --}}
        {{-- =============================================================== --}}
        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Responses over time</h3>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Last 30 days</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format($totalIn30) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">in period</div>
                </div>
            </div>

            <div class="mt-6">
                <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-40" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="rgb(var(--primary-500, 16 185 129))" stop-opacity="0.35" />
                            <stop offset="100%" stop-color="rgb(var(--primary-500, 16 185 129))" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <path d="{{ $tsArea }}" fill="url(#areaGrad)" />
                    <path d="{{ $tsPath }}" fill="none" stroke="rgb(var(--primary-500, 16 185 129))" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                    @foreach ($tsValues as $i => $v)
                        @if ($v > 0)
                            <circle cx="{{ $i * $px }}" cy="{{ $h - (($v / $tsMax) * ($h - 10)) - 5 }}" r="3" fill="rgb(var(--primary-500, 16 185 129))" />
                        @endif
                    @endforeach
                </svg>
            </div>

            <div class="mt-2 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ \Carbon\Carbon::parse(array_key_first($timeSeries))->format('M j') }}</span>
                <span>{{ \Carbon\Carbon::parse(array_keys($timeSeries)[14] ?? '')->format('M j') }}</span>
                <span>Today</span>
            </div>
        </div>

        {{-- =============================================================== --}}
        {{-- DROP-OFF                                                         --}}
        {{-- =============================================================== --}}
        @if (count($dropOff) > 0)
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Question drop-off</h3>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            Where respondents skip — based on {{ $summary['completed'] }} completed response{{ $summary['completed'] === 1 ? '' : 's' }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @foreach ($dropOff as $i => $row)
                        @php
                            $answeredPct = $summary['completed'] > 0 ? round(($row['answered'] / $summary['completed']) * 100, 1) : 0;
                            $skipColor = $row['skip_rate'] >= 30 ? 'red' : ($row['skip_rate'] >= 10 ? 'amber' : 'emerald');
                        @endphp
                        <div class="flex items-center gap-4">
                            <div class="w-6 text-xs text-gray-400 tabular-nums">Q{{ $i + 1 }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-gray-900 dark:text-gray-100 truncate" title="{{ $row['question']->label }}">{{ $row['question']->label }}</div>
                            </div>
                            <div class="flex-1 max-w-md flex items-center gap-1">
                                <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden flex">
                                    <div class="bg-emerald-500 h-full" style="width: {{ $answeredPct }}%"></div>
                                    @if ($row['skipped'] > 0)
                                        <div class="bg-{{ $skipColor }}-300 dark:bg-{{ $skipColor }}-700 h-full" style="width: {{ 100 - $answeredPct }}%"></div>
                                    @endif
                                </div>
                            </div>
                            <div class="w-44 text-right text-sm tabular-nums">
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['answered'] }}</span>
                                <span class="text-gray-400"> / {{ $summary['completed'] }}</span>
                                @if ($row['skipped'] > 0)
                                    <span class="ml-2 text-{{ $skipColor }}-600 dark:text-{{ $skipColor }}-400 text-xs">{{ $row['skip_rate'] }}% skip</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- =============================================================== --}}
        {{-- PER-QUESTION CARDS                                              --}}
        {{-- =============================================================== --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between pt-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Question breakdown</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($analyses) }} questions</span>
            </div>

            @foreach ($analyses as $i => $a)
                @if ($a['kind'] === 'layout')
                    <div class="flex items-center gap-3 px-2 pt-3">
                        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></div>
                        <span class="text-xs uppercase tracking-wider text-gray-400">{{ $a['question']->label }}</span>
                        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></div>
                    </div>
                    @continue
                @endif

                @php($q = $a['question'])

                <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-6 shadow-sm">
                    {{-- card header --}}
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-mono">Q{{ $i + 1 }}</span>
                                <span class="rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $q->type->label() }}</span>
                            </div>
                            <h3 class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $q->label }}</h3>
                        </div>
                        <div class="flex items-center gap-4 text-right tabular-nums shrink-0">
                            <div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $a['n'] }}</div>
                                <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">responses</div>
                            </div>
                            @if (($a['skipped'] ?? 0) > 0)
                                <div>
                                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $a['skip_rate'] }}<span class="text-base">%</span></div>
                                    <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">skipped</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($a['n'] === 0)
                        <div class="mt-6 text-sm italic text-gray-500 dark:text-gray-400">No data yet.</div>
                    @else
                        <div class="mt-6">

                            {{-- ===== CHOICE ===== --}}
                            @if ($a['kind'] === 'choice')
                                <div class="space-y-2.5">
                                    @foreach ($a['rows'] as $row)
                                        @php($isMode = $a['mode'] === $row['value'] && $row['count'] > 0)
                                        <div>
                                            <div class="flex items-baseline justify-between gap-3 mb-1.5">
                                                <div class="flex items-center gap-2 text-sm min-w-0">
                                                    @if ($isMode)
                                                        <span class="inline-flex items-center rounded-md bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 dark:text-amber-400 uppercase tracking-wide">Top</span>
                                                    @endif
                                                    <span class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $row['label'] }}</span>
                                                </div>
                                                <div class="tabular-nums text-sm shrink-0">
                                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['count'] }}</span>
                                                    <span class="text-gray-400 ml-1">{{ $row['percent'] }}%</span>
                                                </div>
                                            </div>
                                            <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                                <div class="h-full rounded-full {{ $isMode ? 'bg-primary-500' : 'bg-primary-300 dark:bg-primary-800' }}" style="width: {{ $row['percent'] }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- ===== MULTI-CHOICE ===== --}}
                            @if ($a['kind'] === 'multi_choice')
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Avg selected</div>
                                        <div class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $a['avg_selections'] }}</div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Respondents</div>
                                        <div class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $a['total_respondents'] }}</div>
                                    </div>
                                </div>
                                <div class="space-y-2.5">
                                    @foreach ($a['rows'] as $row)
                                        <div>
                                            <div class="flex items-baseline justify-between gap-3 mb-1.5">
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $row['label'] }}</span>
                                                <div class="tabular-nums text-sm shrink-0">
                                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['count'] }}</span>
                                                    <span class="text-gray-400 ml-1">{{ $row['percent'] }}%</span>
                                                </div>
                                            </div>
                                            <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                                <div class="h-full rounded-full bg-primary-500" style="width: {{ min($row['percent'], 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 italic">Percentages are out of total respondents — sum can exceed 100% because each respondent picks multiple.</p>
                            @endif

                            {{-- ===== NUMERIC ===== --}}
                            @if ($a['kind'] === 'numeric' && ($a['numbers'] ?? 0) > 0)
                                {{-- Hero stat row --}}
                                <div class="grid grid-cols-3 gap-3 mb-4">
                                    <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4">
                                        <div class="text-[10px] uppercase tracking-wider text-blue-700 dark:text-blue-400 font-semibold">Mean</div>
                                        <div class="mt-1 text-3xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $a['mean'] }}</div>
                                    </div>
                                    <div class="rounded-lg bg-violet-50 dark:bg-violet-950/30 p-4">
                                        <div class="text-[10px] uppercase tracking-wider text-violet-700 dark:text-violet-400 font-semibold">Median</div>
                                        <div class="mt-1 text-3xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $a['median'] }}</div>
                                    </div>
                                    <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/30 p-4">
                                        <div class="text-[10px] uppercase tracking-wider text-emerald-700 dark:text-emerald-400 font-semibold">Mode</div>
                                        <div class="mt-1 text-3xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $a['mode'] }}</div>
                                    </div>
                                </div>

                                {{-- Secondary stats --}}
                                <div class="grid grid-cols-4 gap-3 mb-5">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Std dev</div>
                                        <div class="mt-1 text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ $a['std_dev'] }}</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Min</div>
                                        <div class="mt-1 text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ $a['min'] }}</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Max</div>
                                        <div class="mt-1 text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ $a['max'] }}</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Range</div>
                                        <div class="mt-1 text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ $a['range'] }}</div>
                                    </div>
                                </div>

                                {{-- Distribution histogram --}}
                                @if (! empty($a['distribution']))
                                    <div class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-3">Distribution</div>
                                    @php($maxBin = max(array_map(fn($b) => $b['count'], $a['distribution'])))
                                    <div class="flex items-end gap-2 h-32">
                                        @foreach ($a['distribution'] as $bin)
                                            @php($pct = $maxBin > 0 ? ($bin['count'] / $maxBin) * 100 : 0)
                                            <div class="flex-1 flex flex-col items-center gap-1 group cursor-default" title="{{ $bin['label'] }}: {{ $bin['count'] }} ({{ $bin['percent'] }}%)">
                                                <div class="text-[10px] text-gray-500 tabular-nums">{{ $bin['count'] }}</div>
                                                <div class="w-full bg-primary-500 hover:bg-primary-600 rounded-t-md transition" style="height: {{ $pct }}%; min-height: {{ $bin['count'] > 0 ? '4px' : '0' }};"></div>
                                                <div class="text-[10px] text-gray-500 font-mono">{{ $bin['label'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif

                            {{-- ===== EMAIL ===== --}}
                            @if ($a['kind'] === 'email')
                                @if (! empty($a['domains']))
                                    @php($topDomain = $a['domains'][0] ?? null)
                                    @if ($topDomain)
                                        <div class="rounded-lg bg-primary-50 dark:bg-primary-950/30 p-4 mb-5">
                                            <div class="text-[10px] uppercase tracking-wider text-primary-700 dark:text-primary-400 font-semibold">Top domain</div>
                                            <div class="mt-1 flex items-baseline gap-3">
                                                <div class="text-2xl font-bold font-mono text-gray-900 dark:text-white">{{ $topDomain['label'] }}</div>
                                                <div class="text-sm tabular-nums text-gray-600 dark:text-gray-400">{{ $topDomain['count'] }} ({{ $topDomain['percent'] }}%)</div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-3">Domain breakdown</div>
                                    <div class="space-y-2.5 mb-5">
                                        @foreach ($a['domains'] as $row)
                                            <div>
                                                <div class="flex items-baseline justify-between gap-3 mb-1.5">
                                                    <span class="text-sm font-mono text-gray-900 dark:text-gray-100 truncate">{{ $row['label'] }}</span>
                                                    <div class="tabular-nums text-sm shrink-0">
                                                        <span class="font-semibold">{{ $row['count'] }}</span>
                                                        <span class="text-gray-400 ml-1">{{ $row['percent'] }}%</span>
                                                    </div>
                                                </div>
                                                <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                                    <div class="h-full bg-primary-500 rounded-full" style="width: {{ $row['percent'] }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (! empty($a['sample']))
                                    <div class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-3">Sample</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($a['sample'] as $email)
                                            <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-1 text-xs font-mono text-gray-700 dark:text-gray-300">{{ $email }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            @endif

                            {{-- ===== TEXT ===== --}}
                            @if ($a['kind'] === 'text')
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Avg length</div>
                                        <div class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $a['avg_length'] }}<span class="text-sm text-gray-500 ml-1 font-normal">chars</span></div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Unique words</div>
                                        <div class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ count($a['top_words'] ?? []) }}<span class="text-sm text-gray-500 ml-1 font-normal">/ top 15</span></div>
                                    </div>
                                </div>

                                @if (! empty($a['top_words']))
                                    <div class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-3">Top words</div>
                                    @php($maxWord = max($a['top_words']))
                                    <div class="flex flex-wrap items-center gap-2 mb-5">
                                        @foreach ($a['top_words'] as $word => $count)
                                            @php($intensity = (int) round(($count / $maxWord) * 4))
                                            @php($bg = ['bg-primary-50 dark:bg-primary-950/30 text-primary-700 dark:text-primary-300','bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300','bg-primary-200 dark:bg-primary-800/50 text-primary-800 dark:text-primary-200','bg-primary-300 dark:bg-primary-700 text-primary-900 dark:text-primary-100','bg-primary-500 text-white'][$intensity])
                                            <span class="inline-flex items-center gap-1.5 rounded-full {{ $bg }} px-3 py-1 text-sm font-medium">
                                                {{ $word }}
                                                <span class="text-[10px] opacity-70 tabular-nums">{{ $count }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if (! empty($a['sample']))
                                    <div class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-3">Sample responses</div>
                                    <div class="space-y-2">
                                        @foreach ($a['sample'] as $s)
                                            <div class="rounded-lg border-l-4 border-primary-300 dark:border-primary-700 bg-gray-50 dark:bg-gray-800/40 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300">
                                                {{ \Illuminate\Support\Str::limit($s, 280) }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif

                            {{-- ===== DATE ===== --}}
                            @if ($a['kind'] === 'date')
                                <div class="grid grid-cols-2 gap-3 mb-5">
                                    <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4">
                                        <div class="text-[10px] uppercase tracking-wider text-blue-700 dark:text-blue-400 font-semibold">Earliest</div>
                                        <div class="mt-1 text-lg font-semibold font-mono text-gray-900 dark:text-white">{{ $a['min'] ?? '—' }}</div>
                                    </div>
                                    <div class="rounded-lg bg-violet-50 dark:bg-violet-950/30 p-4">
                                        <div class="text-[10px] uppercase tracking-wider text-violet-700 dark:text-violet-400 font-semibold">Latest</div>
                                        <div class="mt-1 text-lg font-semibold font-mono text-gray-900 dark:text-white">{{ $a['max'] ?? '—' }}</div>
                                    </div>
                                </div>
                                @if (! empty($a['sample']))
                                    <div class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-3">Sample</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($a['sample'] as $stamp)
                                            <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-1 text-xs font-mono text-gray-700 dark:text-gray-300">{{ $stamp }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            @endif

                            {{-- ===== FILE ===== --}}
                            @if ($a['kind'] === 'file')
                                <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
                                    <div class="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $a['count'] }}<span class="text-sm text-gray-500 ml-1 font-normal">files uploaded</span></div>
                                </div>
                                @if (! empty($a['paths']))
                                    <ul class="mt-3 space-y-1">
                                        @foreach ($a['paths'] as $p)
                                            <li class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate">{{ $p }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif

                        </div>
                    @endif
                </div>
            @endforeach
        </div>

    @endif
</x-filament-panels::page>
