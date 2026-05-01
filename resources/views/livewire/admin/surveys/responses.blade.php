<div class="flex flex-col gap-6 p-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <flux:heading size="xl">{{ $survey->title }} — responses</flux:heading>
            <flux:text class="text-zinc-500"><span class="font-mono">/s/{{ $survey->slug }}</span></flux:text>
        </div>
        <div class="flex gap-2 flex-wrap">
            <flux:button :href="route('admin.surveys.show', $survey)" wire:navigate variant="ghost" icon="arrow-left">Back to survey</flux:button>
            <flux:button wire:click="exportCsv" variant="ghost" icon="arrow-down-tray">Export CSV</flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by email"
            icon="magnifying-glass"
            class="md:col-span-2"
        />

        <flux:select wire:model.live="completed" placeholder="All">
            <flux:select.option value="">All responses</flux:select.option>
            <flux:select.option value="yes">Completed only</flux:select.option>
            <flux:select.option value="no">In progress only</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$responses">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Submitted</flux:table.column>
            <flux:table.column>Duration</flux:table.column>
            <flux:table.column>Lead</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($responses as $resp)
                @php
                    $d = $resp->durationSeconds();
                @endphp
                <flux:table.row wire:key="resp-{{ $resp->id }}">
                    <flux:table.cell class="text-xs text-zinc-500 tabular-nums">{{ $resp->id }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-sm">{{ $resp->email ?: '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($resp->completed_at)
                            <flux:badge size="sm" color="emerald">Completed</flux:badge>
                        @else
                            <flux:badge size="sm" color="amber">In progress</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $resp->completed_at?->format('Y-m-d H:i') ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm tabular-nums">{{ $d ? intdiv($d, 60).'m '.($d % 60).'s' : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($resp->lead)
                            <a href="{{ route('admin.leads.show', $resp->lead) }}" wire:navigate class="text-sm text-blue-600 hover:underline">
                                {{ $resp->lead->name ?: $resp->lead->email ?: 'Lead #'.$resp->lead->id }}
                            </a>
                        @else
                            <span class="text-zinc-400 text-sm">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button size="xs" variant="ghost" icon="eye" wire:click="viewResponse({{ $resp->id }})">Answers</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-12 text-center text-zinc-500">No responses match your filters.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Inline answer panel --}}
    @if ($this->viewingResponse)
        @php
            $vr = $this->viewingResponse;
            $answersByQuestionId = $vr->answers->keyBy('survey_question_id');
            $answerableQuestions = [];
            foreach ($vr->survey->questions as $q) {
                if ($q->type->isAnswerable()) {
                    $answerableQuestions[] = $q;
                }
            }
        @endphp
        <div class="rounded-xl border border-blue-200 dark:border-blue-900 bg-blue-50/30 dark:bg-blue-950/20 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">Response #{{ $vr->id }}</flux:heading>
                    <flux:text class="text-zinc-500">
                        {{ $vr->email ?: '—' }} ·
                        Submitted {{ $vr->completed_at?->format('Y-m-d H:i') ?? 'in progress' }}
                    </flux:text>
                </div>
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="closeResponse">Close</flux:button>
            </div>

            <div class="mt-4 space-y-3">
                @foreach ($answerableQuestions as $question)
                    @php
                        $answer = $answersByQuestionId->get($question->id);
                        $rawValue = $answer ? ($answer->value['answer'] ?? null) : null;
                        $display = is_array($rawValue) ? implode(', ', array_map('strval', $rawValue)) : (string) ($rawValue ?? '');
                    @endphp
                    <div class="border-b border-zinc-200 dark:border-zinc-800 pb-3 last:border-b-0">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">{{ $question->label }}</div>
                        <div class="mt-1 text-sm">
                            {!! $display === '' ? '<em class="text-zinc-400">No answer</em>' : e($display) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
