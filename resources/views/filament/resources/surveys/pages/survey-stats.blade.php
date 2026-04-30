<x-filament-panels::page>
    @php($stats = $this->getStats())
    @php($breakdowns = $this->getQuestionBreakdowns())

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <x-filament::section>
            <div class="text-sm text-gray-500">Total responses</div>
            <div class="mt-1 text-3xl font-bold">{{ $stats['total'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500">Completed</div>
            <div class="mt-1 text-3xl font-bold">{{ $stats['completed'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500">Completion rate</div>
            <div class="mt-1 text-3xl font-bold">{{ $stats['completionRate'] }}%</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500">Avg time to complete</div>
            <div class="mt-1 text-3xl font-bold">{{ $stats['avgDuration'] }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Last 7 days</x-slot>

        @php($max = max(array_merge([1], $stats['chart'])))
        <div class="flex items-end gap-2 h-40 mt-4">
            @foreach ($stats['chart'] as $date => $count)
                <div class="flex-1 flex flex-col items-center justify-end gap-2">
                    <div class="text-xs text-gray-500">{{ $count }}</div>
                    <div class="w-full bg-primary-500 rounded-t" style="height: {{ ($count / $max) * 100 }}%; min-height: 2px;"></div>
                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($date)->format('M j') }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    @if (count($breakdowns) > 0)
        <x-filament::section>
            <x-slot name="heading">Answer breakdown</x-slot>

            <div class="space-y-6">
                @foreach ($breakdowns as $b)
                    <div>
                        <div class="font-medium mb-2">{{ $b['question']->label }}</div>
                        @if ($b['total'] === 0)
                            <div class="text-sm text-gray-500">No answers yet.</div>
                        @else
                            <div class="space-y-1">
                                @foreach ($b['counts'] as $value => $count)
                                    @php($pct = $b['total'] > 0 ? round(($count / $b['total']) * 100) : 0)
                                    <div class="flex items-center gap-3">
                                        <div class="w-1/4 text-sm truncate">{{ $value }}</div>
                                        <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden">
                                            <div class="bg-primary-500 h-full rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <div class="w-20 text-right text-sm text-gray-600 dark:text-gray-400">{{ $count }} ({{ $pct }}%)</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
