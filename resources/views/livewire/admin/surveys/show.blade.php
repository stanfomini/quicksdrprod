<div class="flex flex-col gap-6 p-6">
    @php
        $stats = $this->statistics;
        $summary = $stats->summary();
        $timeSeries = $stats->timeSeries(30);
        $analyses = $stats->questionAnalyses();
        $publicUrl = $survey->publicUrl();
        $formatDuration = function (?float $seconds): string {
            if ($seconds === null || $seconds <= 0) return '—';
            $s = (int) round($seconds);
            $m = intdiv($s, 60);
            $r = $s % 60;
            return $m > 0 ? $m.'m '.$r.'s' : $s.'s';
        };
    @endphp

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <flux:heading size="xl">{{ $survey->title }}</flux:heading>
                @if ($survey->status)
                    <flux:badge size="lg" :color="$survey->status->color()">{{ $survey->status->label() }}</flux:badge>
                @endif
                @if ($survey->audience)
                    <flux:badge size="lg" color="zinc">{{ $survey->audience->label() }}</flux:badge>
                @endif
            </div>
            <flux:text class="text-zinc-500 mt-2">
                <span class="font-mono">/s/{{ $survey->slug }}</span> ·
                {{ $survey->questions->count() }} questions ·
                Updated {{ $survey->updated_at?->diffForHumans() }}
            </flux:text>
        </div>
        <div class="flex gap-2 flex-wrap">
            <flux:button :href="route('admin.surveys.index')" wire:navigate variant="ghost" icon="arrow-left">Back</flux:button>
            <flux:button :href="$publicUrl" target="_blank" variant="ghost" icon="arrow-top-right-on-square">View public</flux:button>
            <flux:button :href="route('admin.surveys.responses', $survey)" wire:navigate variant="ghost" icon="inbox-stack">Responses</flux:button>
            <flux:button :href="route('admin.surveys.edit', $survey)" wire:navigate variant="primary" icon="pencil-square">Edit</flux:button>
        </div>
    </div>

    {{-- Public URL & embed --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
        <div class="flex items-start gap-3">
            <flux:icon.link class="w-5 h-5 text-zinc-400 mt-1" />
            <div class="flex-1 min-w-0">
                <div class="text-xs text-zinc-500 uppercase tracking-wide">Public link</div>
                <input
                    type="text"
                    readonly
                    value="{{ $publicUrl }}"
                    onclick="this.select(); document.execCommand('copy');"
                    class="mt-1 w-full bg-transparent border-0 p-0 text-sm font-mono focus:ring-0 cursor-pointer"
                />
            </div>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5">
            <div class="text-xs uppercase tracking-wider text-zinc-500">Total responses</div>
            <div class="mt-1 text-3xl font-bold tabular-nums">{{ $summary['total_responses'] }}</div>
            <div class="text-xs text-zinc-500 mt-1">{{ $summary['in_progress'] }} in progress</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5">
            <div class="text-xs uppercase tracking-wider text-zinc-500">Completed</div>
            <div class="mt-1 text-3xl font-bold tabular-nums">{{ $summary['completed'] }}</div>
            <div class="text-xs text-zinc-500 mt-1">{{ $summary['completion_rate'] }}% rate</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5">
            <div class="text-xs uppercase tracking-wider text-zinc-500">Avg time</div>
            <div class="mt-1 text-3xl font-bold tabular-nums">{{ $formatDuration($summary['avg_duration_seconds']) }}</div>
            <div class="text-xs text-zinc-500 mt-1">median {{ $formatDuration($summary['median_duration_seconds']) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5">
            <div class="text-xs uppercase tracking-wider text-zinc-500">Latest</div>
            <div class="mt-1 text-2xl font-bold">
                @if ($summary['last_response_at'])
                    {{ \Carbon\Carbon::parse($summary['last_response_at'])->diffForHumans(['short' => true]) }}
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    {{-- 30-day chart --}}
    @if ($summary['completed'] > 0)
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="lg">Last 30 days</flux:heading>
            @php
                $tsValues = array_values($timeSeries);
                $tsMax = max(array_merge([1], $tsValues));
                $totalIn30 = array_sum($tsValues);
            @endphp
            <flux:text class="text-zinc-500 mt-1">{{ $totalIn30 }} completed in this window</flux:text>
            <div class="flex items-end gap-1 h-24 mt-4">
                @foreach ($tsValues as $count)
                    @php
                        $pct = ($count / $tsMax) * 100;
                    @endphp
                    <div class="flex-1 flex flex-col items-center justify-end" title="{{ $count }}">
                        <div class="w-full bg-blue-500 dark:bg-blue-600 rounded-t" style="height: {{ $pct }}%; min-height: {{ $count > 0 ? '3px' : '0' }};"></div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-zinc-500 mt-2">
                <span>{{ \Carbon\Carbon::parse(array_key_first($timeSeries))->format('M j') }}</span>
                <span>Today</span>
            </div>
        </div>
    @endif

    {{-- Question breakdown (compact) --}}
    @if (! empty($analyses))
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Question breakdown</flux:heading>
                <flux:button size="xs" variant="ghost" :href="url('/internal/surveys/'.$survey->id.'/stats')" target="_blank">Full stats</flux:button>
            </div>
            <div class="mt-4 space-y-5">
                @foreach ($analyses as $i => $a)
                    @if (($a['kind'] ?? null) === 'layout')
                        @continue
                    @endif
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <div class="text-sm font-medium">
                                <span class="text-zinc-400 mr-1">Q{{ $i + 1 }}</span>{{ $a['question']->label }}
                            </div>
                            <div class="text-xs text-zinc-500 tabular-nums">n={{ $a['n'] ?? 0 }}</div>
                        </div>

                        @if (($a['n'] ?? 0) > 0)
                            @if ($a['kind'] === 'choice' || $a['kind'] === 'multi_choice')
                                <div class="mt-2 space-y-1.5">
                                    @foreach (array_slice($a['rows'] ?? [], 0, 5) as $row)
                                        <div class="flex items-center gap-3">
                                            <div class="w-1/3 text-xs truncate">{{ $row['label'] }}</div>
                                            <div class="flex-1 bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                                                <div class="h-full bg-primary-500" style="width: {{ min($row['percent'], 100) }}%"></div>
                                            </div>
                                            <div class="w-20 text-right text-xs tabular-nums">
                                                <span class="font-semibold">{{ $row['count'] }}</span>
                                                <span class="text-zinc-400">{{ $row['percent'] }}%</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($a['kind'] === 'numeric')
                                <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-xs tabular-nums text-zinc-600 dark:text-zinc-400">
                                    <span>Mean <strong class="text-zinc-900 dark:text-zinc-100">{{ $a['mean'] }}</strong></span>
                                    <span>Median <strong class="text-zinc-900 dark:text-zinc-100">{{ $a['median'] }}</strong></span>
                                    <span>Mode <strong class="text-zinc-900 dark:text-zinc-100">{{ $a['mode'] }}</strong></span>
                                    <span>SD <strong class="text-zinc-900 dark:text-zinc-100">{{ $a['std_dev'] }}</strong></span>
                                    <span>Range <strong class="text-zinc-900 dark:text-zinc-100">{{ $a['min'] }}–{{ $a['max'] }}</strong></span>
                                </div>
                            @elseif ($a['kind'] === 'email')
                                @php
                                    $topDomain = $a['domains'][0] ?? null;
                                @endphp
                                <div class="mt-2 text-xs text-zinc-500">
                                    @if ($topDomain)
                                        Top domain <span class="font-mono">{{ $topDomain['label'] }}</span> ({{ $topDomain['percent'] }}%)
                                    @else
                                        —
                                    @endif
                                </div>
                            @elseif ($a['kind'] === 'text')
                                <div class="mt-2 text-xs text-zinc-500">
                                    Avg length {{ $a['avg_length'] }} chars · Top word: <strong>{{ array_key_first($a['top_words'] ?? []) ?? '—' }}</strong>
                                </div>
                            @endif
                        @else
                            <div class="mt-2 text-xs italic text-zinc-400">No answers yet.</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent responses + Linked leads --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Recent responses</flux:heading>
                <flux:button size="xs" variant="ghost" :href="route('admin.surveys.responses', $survey)" wire:navigate>View all</flux:button>
            </div>
            @if ($this->recentResponses->isEmpty())
                <flux:text class="text-zinc-500 mt-2">No responses yet.</flux:text>
            @else
                <flux:table class="mt-4">
                    <flux:table.columns>
                        <flux:table.column>Email</flux:table.column>
                        <flux:table.column>Submitted</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentResponses as $resp)
                            <flux:table.row wire:key="r-{{ $resp->id }}">
                                <flux:table.cell class="font-mono text-xs">{{ $resp->email ?: '—' }}</flux:table.cell>
                                <flux:table.cell class="text-xs text-zinc-500">{{ $resp->completed_at?->diffForHumans() ?? 'in progress' }}</flux:table.cell>
                                <flux:table.cell class="text-right">
                                    @if ($resp->lead)
                                        <flux:button size="xs" variant="ghost" :href="route('admin.leads.show', $resp->lead)" wire:navigate>Lead</flux:button>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="lg">Leads from this survey</flux:heading>
            @if ($this->linkedLeads->isEmpty())
                <flux:text class="text-zinc-500 mt-2">No leads attached yet.</flux:text>
            @else
                <flux:table class="mt-4">
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Email</flux:table.column>
                        <flux:table.column>Stage</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->linkedLeads as $lead)
                            <flux:table.row wire:key="l-{{ $lead->id }}">
                                <flux:table.cell>{{ $lead->name ?: '—' }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs">{{ $lead->email ?: '—' }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($lead->stage)
                                        <flux:badge size="sm" :color="$lead->stage->color()">{{ $lead->stage->label() }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <flux:button size="xs" variant="ghost" :href="route('admin.leads.show', $lead)" wire:navigate>Open</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    </div>
</div>
