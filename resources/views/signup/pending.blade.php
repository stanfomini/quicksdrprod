<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => __('Signup pending')])
</head>
<body class="min-h-screen bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100 antialiased">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-xl rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8 shadow-2xl backdrop-blur">
            <h1 class="text-2xl font-semibold text-white">
                {{ __('Your signup is pending') }}
            </h1>

            <p class="mt-3 text-zinc-300">
                {{ __('We\'ve saved your details. Once payment is confirmed, provisioning will start automatically.') }}
            </p>

            <p class="mt-2 text-sm text-zinc-400">
                {{ __('If you closed the Stripe Checkout window, you can restart from the signup page.') }}
            </p>

            <div class="mt-6">
                <flux:button :href="route('signup')" variant="primary" icon-trailing="arrow-right">
                    {{ __('Back to signup') }}
                </flux:button>
            </div>
        </div>
    </main>

    @fluxScripts
</body>
</html>
