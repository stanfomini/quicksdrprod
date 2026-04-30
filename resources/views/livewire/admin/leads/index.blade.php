<div class="flex flex-col gap-6 p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Leads</flux:heading>
            <flux:text class="text-zinc-500">Prospects captured from survey responses and manual entry. Convert to tenants on signup.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.leads.create')" wire:navigate>New lead</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search name, email, phone, company"
            icon="magnifying-glass"
            class="md:col-span-2"
        />

        <flux:select wire:model.live="stage" placeholder="All stages">
            <flux:select.option value="">All stages</flux:select.option>
            @foreach ($this->stageOptions as $opt)
                <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="linked" placeholder="Linkage">
            <flux:select.option value="">All leads</flux:select.option>
            <flux:select.option value="tenant">Linked to tenant</flux:select.option>
            <flux:select.option value="no-tenant">Not yet a tenant</flux:select.option>
        </flux:select>

        <div class="flex justify-end">
            <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">Clear filters</flux:button>
        </div>
    </div>

    <flux:table :paginate="$leads">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'name'" :direction="$sortDirection" wire:click="sortBy('name')">Name</flux:table.column>
            <flux:table.column>Contact</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'company'" :direction="$sortDirection" wire:click="sortBy('company')">Company</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'stage'" :direction="$sortDirection" wire:click="sortBy('stage')">Stage</flux:table.column>
            <flux:table.column>Resp.</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'created_at'" :direction="$sortDirection" wire:click="sortBy('created_at')">Age</flux:table.column>
            <flux:table.column>Tenant</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($leads as $lead)
                @php
                    $age = $lead->ageInDays();
                    $isClosed = $lead->stage->isClosed();
                    $ageColor = ($age >= 14 && ! $isClosed) ? 'text-red-600 dark:text-red-400'
                        : (($age >= 7 && ! $isClosed) ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500');
                @endphp
                <flux:table.row wire:key="lead-{{ $lead->id }}">
                    <flux:table.cell>
                        <a href="{{ route('admin.leads.show', $lead) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">
                            {{ $lead->name ?: '—' }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-col text-xs font-mono">
                            <span>{{ $lead->email ?: '—' }}</span>
                            <span class="text-zinc-500">{{ $lead->phone ?: '' }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $lead->company ?: '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($lead->stage)
                            <flux:badge size="sm" :color="$lead->stage->color()">{{ $lead->stage->label() }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($lead->responses_count > 0)
                            <flux:badge size="sm" color="zinc">{{ $lead->responses_count }}</flux:badge>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm {{ $ageColor }}">
                            <div>{{ $lead->created_at?->diffForHumans() }}</div>
                            <div class="text-xs opacity-70">{{ $age === 0 ? 'today' : $age.'d old' }}</div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($lead->tenant)
                            <a href="{{ route('admin.tenants.show', $lead->tenant) }}" wire:navigate>
                                <flux:badge size="sm" color="emerald" icon="check-badge">{{ $lead->tenant->subdomain }}</flux:badge>
                            </a>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button size="xs" variant="ghost" icon="pencil-square" :href="route('admin.leads.edit', $lead)" wire:navigate>Edit</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="py-12 text-center text-zinc-500">
                        No leads yet. They'll appear automatically as people submit surveys with email or phone questions.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
