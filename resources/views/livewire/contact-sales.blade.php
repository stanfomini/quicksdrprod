<div class="mx-auto max-w-2xl py-16 px-6">
    @if ($submitted)
        <div class="text-center space-y-6">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                <flux:icon name="check" class="size-8 text-emerald-600 dark:text-emerald-400" />
            </div>
            <flux:heading size="xl">Thanks, {{ $name }} — we got it.</flux:heading>
            <flux:text>
                Matthew will reach out within one business day at
                <span class="font-medium">{{ $phone }}</span> or
                <span class="font-medium">{{ $email }}</span>.
            </flux:text>
            <flux:button :href="route('home')" variant="ghost">Back to home</flux:button>
        </div>
    @else
        <div class="space-y-2 mb-8">
            <flux:heading size="xl">Custom build inquiry</flux:heading>
            <flux:text>
                Tier 4 is hourly consulting ($160/hr) for custom AI or automation work
                that goes beyond the standard QuickSDR platform. Tell us what you're
                thinking and we'll get on a call.
            </flux:text>
        </div>

        <form wire:submit="submit" class="space-y-5">
            <flux:input wire:model.blur="name" label="Your name" required />
            <flux:input wire:model.blur="email" label="Email" type="email" required />
            <flux:input wire:model.blur="phone" label="Phone" type="tel" required />
            <flux:input wire:model.blur="company" label="Company" required />
            <flux:textarea
                wire:model.blur="scope"
                label="What are you looking to build?"
                rows="6"
                placeholder="A few sentences on your use case, current stack, and timeline helps us prep for the call."
                required
            />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="submit">Send inquiry</span>
                    <span wire:loading wire:target="submit">Sending…</span>
                </flux:button>
            </div>
        </form>
    @endif
</div>
