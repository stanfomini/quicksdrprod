<div class="mx-auto max-w-md py-24 px-6">
    <div class="space-y-2 mb-8 text-center">
        <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 mb-4">
            <flux:icon name="envelope" class="size-6 text-white" />
        </div>
        <flux:heading size="xl">Check your email for a code</flux:heading>
        <flux:text>
            We sent a 6-digit code to
            <span class="font-medium text-zinc-200">{{ $maskedEmail }}</span>.
            It expires in 5 minutes.
        </flux:text>
    </div>

    @if ($flash)
        <flux:callout variant="secondary" class="mb-6">
            <flux:callout.text>{{ $flash }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="verify" class="space-y-5">
        <flux:input
            wire:model="code"
            label="6-digit code"
            placeholder="000000"
            maxlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            autofocus
            required
        />

        <flux:button type="submit" variant="primary" class="w-full">
            <span wire:loading.remove wire:target="verify">Verify and continue</span>
            <span wire:loading wire:target="verify">Verifying…</span>
        </flux:button>
    </form>

    <div class="mt-8 flex items-center justify-between text-sm">
        <button
            type="button"
            wire:click="resend"
            wire:loading.attr="disabled"
            class="text-cyan-400 hover:text-cyan-300 underline underline-offset-4"
        >
            Send me a new code
        </button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-zinc-500 hover:text-zinc-400">
                Sign out
            </button>
        </form>
    </div>
</div>
