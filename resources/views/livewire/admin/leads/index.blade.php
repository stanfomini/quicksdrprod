<div class="flex flex-col gap-6 p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Leads</flux:heading>
            <flux:text class="text-zinc-500">Prospects captured from survey responses and manual entry. Convert to tenants on signup.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.leads.create')" wire:navigate>New lead</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search name, email, phone, company"
            icon="magnifying-glass"
            class="md:col-span-2"
        />

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
            <flux:table.column sortable :sorted="$sortField === 'email'" :direction="$sortDirection" wire:click="sortBy('email')">Email</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'phone'" :direction="$sortDirection" wire:click="sortBy('phone')">Phone</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'company'" :direction="$sortDirection" wire:click="sortBy('company')">Company</flux:table.column>
            <flux:table.column>Responses</flux:table.column>
            <flux:table.column>Tenant</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'last_seen_at'" :direction="$sortDirection" wire:click="sortBy('last_seen_at')">Last seen</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($leads as $lead)
                <flux:table.row wire:key="lead-{{ $lead->id }}">
                    <flux:table.cell>
                        <a href="{{ route('admin.leads.show', $lead) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">
                            {{ $lead->name ?: '—' }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-sm">{{ $lead->email ?: '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-sm">{{ $lead->phone ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $lead->company ?: '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($lead->responses_count > 0)
                            <flux:badge size="sm" color="zinc">{{ $lead->responses_count }}</flux:badge>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($lead->tenant)
                            <a href="{{ route('admin.tenants.show', $lead->tenant) }}" wire:navigate>
                                <flux:badge size="sm" color="emerald" icon="check-badge">{{ $lead->tenant->subdomain }}</flux:badge>
                            </a>
                        @else
                            <flux:badge size="sm" color="zinc" variant="solid:outline">Lead</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $lead->last_seen_at?->diffForHumans() ?? '—' }}</flux:table.cell>
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
