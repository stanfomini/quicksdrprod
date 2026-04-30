<div class="flex flex-col gap-6 p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <flux:heading size="xl">{{ $lead->name ?: 'Unnamed lead' }}</flux:heading>
                <flux:badge size="lg" :color="$lead->stage->color()">{{ $lead->stage->label() }}</flux:badge>
                @if ($lead->tenant)
                    <flux:badge color="emerald" icon="check-badge">Converted to tenant</flux:badge>
                @endif
            </div>
            <flux:text class="text-zinc-500 mt-1">
                @if ($lead->source)
                    Source: <span class="font-mono">{{ $lead->source }}</span> ·
                @endif
                Created {{ $lead->created_at->diffForHumans() }} ({{ $lead->ageInDays() }}d ago)
                @if ($lead->last_seen_at && $lead->last_seen_at->ne($lead->created_at))
                    · Last seen {{ $lead->last_seen_at->diffForHumans() }}
                @endif
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.leads.index')" wire:navigate variant="ghost" icon="arrow-left">Back</flux:button>
            <flux:button :href="route('admin.leads.edit', $lead)" wire:navigate variant="primary" icon="pencil-square">Edit</flux:button>
        </div>
    </div>

    {{-- Stage management --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
        <flux:heading size="lg">Pipeline stage</flux:heading>
        <flux:text class="text-zinc-500 mt-1">Move this lead through your sales pipeline.</flux:text>

        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($this->stageOptions as $opt)
                @php
                    $isCurrent = $lead->stage->value === $opt['value'];
                    $btnClasses = $isCurrent
                        ? 'px-3 py-1.5 rounded-md text-sm font-medium border transition bg-'.$opt['color'].'-500 text-white border-transparent shadow-sm'
                        : 'px-3 py-1.5 rounded-md text-sm font-medium border transition bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700';
                @endphp
                <button
                    type="button"
                    wire:click="changeStage('{{ $opt['value'] }}')"
                    class="{{ $btnClasses }}"
                >{{ $opt['label'] }}</button>
            @endforeach
        </div>
    </div>

    {{-- Contact details --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
        <flux:heading size="lg" class="mb-4">Contact</flux:heading>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <dt class="text-xs uppercase tracking-wide text-zinc-500">Email</dt>
                <dd class="mt-1 font-mono">
                    @if ($lead->email)
                        <a href="mailto:{{ $lead->email }}" class="text-blue-600 hover:underline">{{ $lead->email }}</a>
                    @else
                        <span class="text-zinc-400">—</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-zinc-500">Phone</dt>
                <dd class="mt-1 font-mono">
                    @if ($lead->phone)
                        <a href="tel:{{ $lead->phone }}" class="text-blue-600 hover:underline">{{ $lead->phone }}</a>
                    @else
                        <span class="text-zinc-400">—</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-zinc-500">Company</dt>
                <dd class="mt-1">{{ $lead->company ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-zinc-500">Source</dt>
                <dd class="mt-1 font-mono text-xs">{{ $lead->source ?: '—' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Linked tenant --}}
    @if ($lead->tenant)
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/30 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">Linked tenant</flux:heading>
                    <flux:text class="mt-1">This lead converted into a paying tenant.</flux:text>
                    <div class="mt-3 grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                        <div><span class="text-zinc-500">Business:</span> <strong>{{ $lead->tenant->business_name }}</strong></div>
                        <div><span class="text-zinc-500">Subdomain:</span> <span class="font-mono">{{ $lead->tenant->subdomain }}</span></div>
                        <div><span class="text-zinc-500">Tier:</span> {{ $lead->tenant->tier?->label() ?? '—' }}</div>
                        <div><span class="text-zinc-500">Status:</span> {{ $lead->tenant->status?->label() ?? '—' }}</div>
                    </div>
                </div>
                <flux:button :href="route('admin.tenants.show', $lead->tenant)" wire:navigate variant="primary" icon="arrow-top-right-on-square">Open tenant</flux:button>
            </div>
        </div>
    @endif

    {{-- Survey responses --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
        <flux:heading size="lg" class="mb-4">Survey responses ({{ $this->responses->count() }})</flux:heading>

        @if ($this->responses->isEmpty())
            <flux:text class="text-zinc-500">No survey responses linked to this lead yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Survey</flux:table.column>
                    <flux:table.column>Public link</flux:table.column>
                    <flux:table.column>Submitted</flux:table.column>
                    <flux:table.column>Duration</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->responses as $response)
                        <flux:table.row wire:key="resp-{{ $response->id }}">
                            <flux:table.cell>
                                <strong>{{ $response->survey->title }}</strong>
                                <div class="text-xs text-zinc-500 font-mono">{{ $response->survey->slug }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <a href="{{ $response->survey->publicUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline font-mono truncate max-w-xs">
                                    /s/{{ $response->survey->slug }}
                                    <flux:icon.arrow-top-right-on-square class="w-3 h-3" />
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $response->completed_at?->format('Y-m-d H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $d = $response->durationSeconds();
                                @endphp
                                {{ $d ? intdiv($d, 60).'m '.($d % 60).'s' : '—' }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button size="xs" variant="ghost" icon="eye" wire:click="viewResponse({{ $response->id }})">Answers</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

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
                    <flux:heading size="lg">Answers — {{ $vr->survey->title }}</flux:heading>
                    <flux:text class="text-zinc-500">Submitted {{ $vr->completed_at?->format('Y-m-d H:i') ?? '—' }}</flux:text>
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
