<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => __('Payment received — provisioning your QuickSDR')])
</head>
<body class="min-h-screen bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100 antialiased">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-xl rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8 shadow-2xl backdrop-blur">
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 ring-1 ring-emerald-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>

            <h1 class="text-2xl font-semibold text-white">
                {{ __('Payment received') }}
            </h1>

            <p class="mt-3 text-zinc-300">
                <strong class="text-white">{{ __('Your QuickSDR is being provisioned.') }}</strong>
                {{ __('You\'ll get an email with login details within 15 minutes.') }}
            </p>

            <p class="mt-2 text-sm text-zinc-400">
                {{ __('Typical time: under 10 minutes.') }}
            </p>

            <div class="mt-6 rounded-xl border border-zinc-800 bg-zinc-900/40 p-4">
                <p class="mb-3 text-xs uppercase tracking-wider text-zinc-500">
                    {{ __('Live status') }}
                </p>
                <livewire:provisioning-status :tenant-id="$tenant->id" />
            </div>

            <div class="mt-6 text-sm text-zinc-400">
                {{ __('Your dialer will be at:') }}
                <span class="font-medium text-zinc-200">{{ $tenant->primaryHost() }}</span>
            </div>
        </div>
    </main>

    @fluxScripts
</body>
</html>
