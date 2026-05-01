<div class="flex flex-col gap-6 p-6 max-w-2xl">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">New survey</flux:heading>
            <flux:text class="text-zinc-500">Set the basics. You'll add questions on the next screen.</flux:text>
        </div>
        <flux:button :href="route('admin.surveys.index')" wire:navigate variant="ghost" icon="arrow-left">Cancel</flux:button>
    </div>

    <form wire:submit="save" class="flex flex-col gap-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
        <flux:input wire:model.live.debounce.300ms="title" label="Title" required placeholder="e.g. Sales discovery" />
        <flux:input wire:model="slug" label="Slug" required description="Used in the public URL: /s/{slug}" />
        <flux:select wire:model="audience" label="Audience" required placeholder="Choose...">
            @foreach ($this->audienceOptions as $opt)
                <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2 pt-2">
            <flux:button :href="route('admin.surveys.index')" wire:navigate variant="ghost">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Create &amp; edit questions</flux:button>
        </div>
    </form>
</div>
