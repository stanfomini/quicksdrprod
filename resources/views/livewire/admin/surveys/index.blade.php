<div class="flex flex-col gap-6 p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Surveys</flux:heading>
            <flux:text class="text-zinc-500">Build, share, and analyze surveys. Edits open the Filament editor inline.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.surveys.create')" wire:navigate>New survey</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search title or slug"
            icon="magnifying-glass"
            class="md:col-span-2"
        />

        <flux:select wire:model.live="status" placeholder="All statuses">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach ($this->statusOptions as $opt)
                <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="audience" placeholder="All audiences">
            <flux:select.option value="">All audiences</flux:select.option>
            @foreach ($this->audienceOptions as $opt)
                <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end">
            <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">Clear</flux:button>
        </div>
    </div>

    <flux:table :paginate="$surveys">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'title'" :direction="$sortDirection" wire:click="sortBy('title')">Title</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'audience'" :direction="$sortDirection" wire:click="sortBy('audience')">Audience</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'status'" :direction="$sortDirection" wire:click="sortBy('status')">Status</flux:table.column>
            <flux:table.column>Questions</flux:table.column>
            <flux:table.column>Responses</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'updated_at'" :direction="$sortDirection" wire:click="sortBy('updated_at')">Updated</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($surveys as $survey)
                <flux:table.row wire:key="survey-{{ $survey->id }}">
                    <flux:table.cell>
                        <a href="{{ route('admin.surveys.show', $survey) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">
                            {{ $survey->title }}
                        </a>
                        <div class="text-xs text-zinc-500 font-mono">{{ $survey->slug }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($survey->audience)
                            <flux:badge size="sm" color="zinc">{{ $survey->audience->label() }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($survey->status)
                            <flux:badge size="sm" :color="$survey->status->color()">{{ $survey->status->label() }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="tabular-nums">{{ $survey->questions_count }}</flux:table.cell>
                    <flux:table.cell class="tabular-nums">{{ $survey->responses_count }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $survey->updated_at?->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:button size="xs" variant="ghost" icon="pencil-square" :href="route('admin.surveys.edit', $survey)" wire:navigate>Edit</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-12 text-center text-zinc-500">
                        No surveys yet. Click "New survey" to create one.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
