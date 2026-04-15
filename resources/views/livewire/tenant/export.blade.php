<div class="mx-auto max-w-3xl py-12 px-6 space-y-8">
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-6 space-y-3">
        <div class="flex items-center gap-3">
            <flux:icon name="exclamation-triangle" class="size-6 text-amber-600 dark:text-amber-400" />
            <flux:heading size="lg">Your subscription was cancelled</flux:heading>
        </div>
        <flux:text>
            You have <span class="font-semibold">read-only access</span> to your data for a limited time. Export your
            call history and contacts below. After the grace period expires, this instance will be destroyed.
        </flux:text>
        @if ($deadline)
            <flux:text class="text-sm">
                <span class="font-medium">Grace period ends:</span>
                {{ $deadline->toDayDateTimeString() }}
                @if ($daysLeft !== null)
                    — {{ $daysLeft }} {{ Str::plural('day', $daysLeft) }} remaining
                @endif
            </flux:text>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <flux:card>
            <div class="space-y-3">
                <flux:heading size="md">Call history</flux:heading>
                <flux:text>{{ number_format($callCount) }} calls</flux:text>
                <flux:button wire:click="exportCalls" variant="primary" class="w-full">
                    <span wire:loading.remove wire:target="exportCalls">Download CSV</span>
                    <span wire:loading wire:target="exportCalls">Preparing…</span>
                </flux:button>
            </div>
        </flux:card>

        <flux:card>
            <div class="space-y-3">
                <flux:heading size="md">Contacts</flux:heading>
                <flux:text>{{ number_format($contactCount) }} contacts</flux:text>
                <flux:button wire:click="exportContacts" variant="primary" class="w-full">
                    <span wire:loading.remove wire:target="exportContacts">Download CSV</span>
                    <span wire:loading wire:target="exportContacts">Preparing…</span>
                </flux:button>
            </div>
        </flux:card>
    </div>

    <div class="text-center text-sm text-zinc-500">
        Need to reactivate your subscription? Email
        <a href="mailto:matthew@scriptandautomations.com" class="underline">matthew@scriptandautomations.com</a>
        or call (757) 343-3032.
    </div>
</div>
