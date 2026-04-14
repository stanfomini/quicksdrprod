<div wire:poll.5s class="flex items-center gap-3">
    @if ($this->tenant)
        <flux:badge color="{{ $this->tenant->status->color() }}" size="lg">
            {{ $this->tenant->status->label() }}
        </flux:badge>

        @if ($this->tenant->status_step > 0)
            <span class="text-sm text-zinc-400">
                {{ __('Step :step', ['step' => $this->tenant->status_step]) }}
            </span>
        @endif
    @else
        <flux:badge color="zinc" size="lg">{{ __('Unknown') }}</flux:badge>
    @endif
</div>
