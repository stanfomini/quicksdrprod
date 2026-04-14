<div
    class="flex flex-col gap-6 p-6"
    x-data
    x-init="
        if (typeof window.Echo !== 'undefined') {
            window.Echo.private('admin.tenants')
                .listen('.tenant.ready', () => $wire.$refresh())
                .listen('.tenant.status-changed', () => $wire.$refresh());
        }
    "
>
    <flux:heading size="xl">Tenants</flux:heading>
    <flux:text class="text-zinc-500">All provisioned SaaS tenants. Search, filter, and drill into provisioning logs.</flux:text>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search business, subdomain, email"
            icon="magnifying-glass"
        />

        <flux:select wire:model.live="status" placeholder="All statuses">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach ($this->statusOptions as $opt)
                <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="tier" placeholder="All tiers">
            <flux:select.option value="">All tiers</flux:select.option>
            @foreach ($this->tierOptions as $opt)
                <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end">
            <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">Clear filters</flux:button>
        </div>
    </div>

    <flux:table :paginate="$tenants">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'subdomain'" :direction="$sortDirection" wire:click="sortBy('subdomain')">Subdomain</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'tier'" :direction="$sortDirection" wire:click="sortBy('tier')">Tier</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'status'" :direction="$sortDirection" wire:click="sortBy('status')">Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'created_at'" :direction="$sortDirection" wire:click="sortBy('created_at')">Created</flux:table.column>
            <flux:table.column>Last stat</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'provisioned_at'" :direction="$sortDirection" wire:click="sortBy('provisioned_at')">Provisioned</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($tenants as $tenant)
                <flux:table.row :key="$tenant->id">
                    <flux:table.cell>
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                            {{ $tenant->subdomain }}.quicksdr.com
                        </a>
                        <div class="text-xs text-zinc-500">{{ $tenant->business_name }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">{{ $tenant->tier->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$tenant->status->color()">{{ $tenant->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span title="{{ $tenant->created_at }}">{{ $tenant->created_at?->diffForHumans() }}</span>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($last = $latestIngests[$tenant->id] ?? null)
                        @if ($last)
                            <span title="{{ $last }}">{{ \Illuminate\Support\Carbon::parse($last)->diffForHumans() }}</span>
                        @else
                            <span class="text-zinc-500">never</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($tenant->provisioned_at)
                            <span title="{{ $tenant->provisioned_at }}">{{ $tenant->provisioned_at->diffForHumans() }}</span>
                        @else
                            <span class="text-zinc-500">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="xs" variant="ghost" icon-trailing="arrow-right" :href="route('admin.tenants.show', $tenant)" wire:navigate>View</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500">No tenants match the current filters.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
