<div
    class="flex flex-col gap-4 p-6"
    x-data="{ savedAt: null }"
    x-init="
        window.addEventListener('message', function (e) {
            if (e.data && e.data.type === 'survey.saved') {
                savedAt = new Date().toLocaleTimeString();
            }
        });
    "
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <flux:heading size="xl">{{ $survey->title }}</flux:heading>
                @if ($survey->status)
                    <flux:badge size="lg" :color="$survey->status->color()">{{ $survey->status->label() }}</flux:badge>
                @endif
            </div>
            <flux:text class="text-zinc-500 mt-1">
                <span class="font-mono">/s/{{ $survey->slug }}</span> ·
                <span x-show="!savedAt">Edits save automatically when you click Save below.</span>
                <span x-show="savedAt" x-cloak class="text-emerald-600 dark:text-emerald-400">Saved at <span x-text="savedAt"></span></span>
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.surveys.index')" wire:navigate variant="ghost" icon="arrow-left">Back</flux:button>
            <flux:button :href="route('admin.surveys.show', $survey)" wire:navigate variant="ghost" icon="chart-bar">Stats</flux:button>
            <flux:button :href="$survey->publicUrl()" target="_blank" variant="ghost" icon="arrow-top-right-on-square">View public</flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <iframe
            src="{{ $embedUrl }}"
            class="w-full block"
            style="height: calc(100vh - 220px); min-height: 600px; border: 0; background: transparent;"
            title="Edit survey"
            allow="clipboard-write"
        ></iframe>
    </div>
</div>
