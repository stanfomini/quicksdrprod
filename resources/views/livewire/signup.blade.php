<div class="flex flex-col gap-10">
    <div class="mx-auto max-w-3xl text-center">
        <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
            {{ __('Start your QuickSDR') }}
        </h1>
        <p class="mt-4 text-lg text-zinc-400">
            {{ __('Pick a plan, tell us who you are, and you\'ll be dialing within 15 minutes.') }}
        </p>
    </div>

    {{-- Tier cards (Tier 1/2/3) --}}
    <div class="grid gap-6 md:grid-cols-3">
        @foreach ($tiers as $tierCase)
            @php
                $value = $tierCase->value;
                $selected = $tier === $value;
                $monthly = number_format($tierCase->monthlyPriceCents() / 100, 0);
                $setup = $tierCase->setupFeeCents();
                $freeMonths = $tierCase->freeTrialMonths();
            @endphp

            <button
                type="button"
                wire:click="selectTier('{{ $value }}')"
                class="group flex flex-col rounded-2xl border p-6 text-left transition
                    {{ $selected
                        ? 'border-indigo-500/70 bg-indigo-500/10 ring-2 ring-indigo-500/40'
                        : 'border-zinc-800 bg-zinc-900/40 hover:border-indigo-500/40 hover:bg-zinc-900/80' }}"
            >
                <div class="flex items-center justify-between">
                    <span class="text-lg font-semibold text-white">{{ $tierCase->label() }}</span>
                    @if ($selected)
                        <flux:badge color="indigo" size="sm">{{ __('Selected') }}</flux:badge>
                    @endif
                </div>

                <div class="mt-4 flex items-baseline gap-1">
                    <span class="text-3xl font-semibold text-white">${{ $monthly }}</span>
                    <span class="text-sm text-zinc-400">/mo</span>
                </div>

                @if ($setup > 0)
                    <p class="mt-1 text-sm text-zinc-400">
                        {{ __('+ $:amount one-time setup', ['amount' => number_format($setup / 100, 0)]) }}
                    </p>
                @else
                    <p class="mt-1 text-sm text-zinc-500">{{ __('No setup fee') }}</p>
                @endif

                @if ($freeMonths > 0)
                    <p class="mt-1 text-sm text-emerald-400">
                        {{ __('First :months months waived', ['months' => $freeMonths]) }}
                    </p>
                @endif

                <p class="mt-5 text-sm leading-relaxed text-zinc-300">
                    {{ $tierBenefits[$value] }}
                </p>
            </button>
        @endforeach
    </div>

    <div class="-mt-4 text-center text-sm text-zinc-400">
        {{ __('Need custom integration work?') }}
        <a href="/contact-sales" class="font-medium text-indigo-400 hover:text-indigo-300">
            {{ __('Contact sales →') }}
        </a>
    </div>

    @error('tier')
        <div class="rounded-md border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-sm text-rose-300">
            {{ $message }}
        </div>
    @enderror

    {{-- Form --}}
    <flux:card class="mx-auto w-full max-w-3xl">
        <form wire:submit="submit" class="flex flex-col gap-5">
            <div class="grid gap-5 md:grid-cols-2">
                <flux:input
                    wire:model.blur="businessName"
                    :label="__('Business name')"
                    placeholder="Acme Inc."
                    required
                />

                <flux:input
                    wire:model.blur="roleTitle"
                    :label="__('Your role / title')"
                    placeholder="Head of Sales"
                    required
                />

                <flux:input
                    wire:model.blur="adminName"
                    :label="__('Your name')"
                    placeholder="Jane Smith"
                    required
                />

                <flux:input
                    wire:model.blur="adminEmail"
                    :label="__('Work email')"
                    type="email"
                    placeholder="jane@acme.com"
                    required
                />

                <flux:input
                    wire:model.blur="adminPhone"
                    :label="__('Phone number')"
                    type="tel"
                    placeholder="+1 555 123 4567"
                    required
                />

                <flux:input
                    wire:model.blur="companyWebsite"
                    :label="__('Company website (optional)')"
                    type="url"
                    placeholder="https://acme.com"
                />
            </div>

            <div>
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <flux:input
                            wire:model.live.debounce.400ms="subdomain"
                            :label="__('Choose your subdomain')"
                            :description="__('Your dialer will live at yourname.quicksdr.com')"
                            placeholder="acme"
                            required
                        />
                    </div>
                    @if ($subdomainStatus !== '')
                        <div class="pb-2">
                            @if ($subdomainStatus === 'available')
                                <flux:badge color="green" size="sm">{{ __('available') }}</flux:badge>
                            @elseif ($subdomainStatus === 'taken')
                                <flux:badge color="red" size="sm">{{ __('taken') }}</flux:badge>
                            @elseif ($subdomainStatus === 'reserved')
                                <flux:badge color="red" size="sm">{{ __('reserved') }}</flux:badge>
                            @elseif ($subdomainStatus === 'invalid')
                                <flux:badge color="amber" size="sm">{{ __('invalid') }}</flux:badge>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($subdomain !== '' && $subdomainStatus === 'available')
                    <p class="mt-2 text-sm text-emerald-400">
                        {{ $subdomain }}.quicksdr.com {{ __('is available') }}
                    </p>
                @endif
            </div>

            <div class="mt-4 flex flex-col gap-3 border-t border-zinc-800 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-zinc-400">
                    {{ __('You\'ll be taken to Stripe to complete payment.') }}
                </p>
                <flux:button
                    type="submit"
                    variant="primary"
                    icon-trailing="arrow-right"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                >
                    <span wire:loading.remove wire:target="submit">{{ __('Continue to payment') }}</span>
                    <span wire:loading wire:target="submit">{{ __('Redirecting…') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
