<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => __('Your QuickSDR is on the way')])
</head>
<body class="min-h-screen bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100 antialiased">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-2xl rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8 text-center shadow-2xl backdrop-blur">
            @php
                $tier = $tenant?->tier?->value ?? 'tier1';
                $tier1Manual = $tier === 'tier1' && ! $tenant?->stripe_customer_id;
            @endphp

            @if ($tier === 'tier1' && ! $tier1Manual)
                <div class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 ring-1 ring-emerald-500/30">
                    <flux:icon name="check-circle" class="size-10" />
                </div>
                <h1 class="text-2xl font-semibold text-white">
                    {{ __('Payment received — provisioning in progress') }}
                </h1>
                <p class="mt-3 text-zinc-300">
                    {{ __('Your dedicated QuickSDR instance is spinning up right now. You\'ll get an email with your login link and first-time setup instructions within 10–15 minutes.') }}
                </p>
            @elseif ($tier1Manual)
                <div class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-cyan-500/10 text-cyan-400 ring-1 ring-cyan-500/30">
                    <flux:icon name="envelope" class="size-10" />
                </div>
                <h1 class="text-2xl font-semibold text-white">
                    {{ __('Thanks — we\'ll reach out to complete your setup') }}
                </h1>
                <p class="mt-3 text-zinc-300">
                    {{ __('We got your signup. Matthew will email you a secure payment link and walk through kickoff within one business day. Your subdomain is reserved.') }}
                </p>
            @elseif (in_array($tier, ['tier2', 'tier3']))
                <div class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-cyan-500/10 text-cyan-400 ring-1 ring-cyan-500/30">
                    <flux:icon name="calendar-days" class="size-10" />
                </div>
                <h1 class="text-2xl font-semibold text-white">
                    {{ __('We\'ve got your info — book your call') }}
                </h1>
                <p class="mt-3 text-zinc-300">
                    {{ __('Matthew will be calling you shortly. If you haven\'t booked your kickoff call yet, pick a time that works for you:') }}
                </p>
                @if (config('services.google.calendar_booking_url'))
                    <div class="mt-6">
                        <flux:button :href="config('services.google.calendar_booking_url')" variant="primary" target="_blank">
                            {{ __('Pick a time') }}
                        </flux:button>
                    </div>
                @endif
            @else
                <div class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-cyan-500/10 text-cyan-400 ring-1 ring-cyan-500/30">
                    <flux:icon name="envelope" class="size-10" />
                </div>
                <h1 class="text-2xl font-semibold text-white">
                    {{ __('Inquiry received') }}
                </h1>
                <p class="mt-3 text-zinc-300">
                    {{ __('Matthew will reach out within one business day.') }}
                </p>
            @endif

            <div class="mt-8">
                <flux:link href="/" variant="ghost">← {{ __('Back to home') }}</flux:link>
            </div>
        </div>
    </main>

    @fluxScripts
</body>
</html>
