<div class="flex flex-col gap-6 p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $lead->name ?: 'Unnamed lead' }}</flux:heading>
                @if ($lead->tenant)
                    <flux:badge color="emerald" icon="check-badge">Converted to tenant</flux:badge>
                @else
                    <flux:badge color="zinc" variant="solid:outline">Lead</flux:badge>
                @endif
            </div>
            <flux:text class="text-zinc-500">
                @if ($lead->source)
                    Source: <span class="font-mono">{{ $lead->source }}</span> ·
                @endif
                Created {{ $lead->created_at->diffForHumans() }}
                @if ($lead->last_seen_at)
                    · Last seen {{ $lead->last_seen_at->diffForHumans() }}
                @endif
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.leads.index')" wire:navigate variant="ghost" icon="arrow-left">Back</flux:button>
            <flux:button :href="route('admin.leads.edit', $lead)" wire:navigate variant="primary" icon="pencil-square">Edit</flux:button>
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
        <flux:heading size="lg" class="mb-4">Survey responses ({{ $responses->count() }})</flux:heading>

        @if ($responses->isEmpty())
            <flux:text class="text-zinc-500">No survey responses linked to this lead yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Survey</flux:table.column>
                    <flux:table.column>Completed</flux:table.column>
                    <flux:table.column>Duration</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($responses as $response)
                        <flux:table.row wire:key="resp-{{ $response->id }}">
                            <flux:table.cell>
                                <strong>{{ $response->survey->title }}</strong>
                                <div class="text-xs text-zinc-500 font-mono">{{ $response->survey->slug }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $response->completed_at?->format('Y-m-d H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @php($d = $response->durationSeconds())
                                {{ $d ? intdiv($d, 60).'m '.($d % 60).'s' : '—' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
