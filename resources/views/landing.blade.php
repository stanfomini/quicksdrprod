<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => __('Power Dialer for Closers')])
    <style>
        /* Radial accent blob anchored to the hero */
        .hero-glow::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(60rem 40rem at 50% -10%, rgba(99, 102, 241, 0.18), transparent 60%),
                radial-gradient(40rem 30rem at 80% 30%, rgba(217, 70, 239, 0.10), transparent 60%);
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
    {{-- ──────────────────────────────────────────────────────────
        Top nav (sticky)
        ────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-50 border-b border-zinc-900/80 bg-zinc-950/70 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-semibold tracking-tight text-white">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-gradient-to-br from-indigo-500 to-fuchsia-500 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.272.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                </span>
                QuickSDR
            </a>
            <flux:button :href="route('login')" variant="primary" size="sm" icon-trailing="arrow-right">
                {{ __('Sign In') }}
            </flux:button>
        </div>
    </header>

    {{-- ──────────────────────────────────────────────────────────
        Hero — headline + subhead + embedded login form
        ────────────────────────────────────────────────────────── --}}
    <section class="hero-glow relative overflow-hidden bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900">
        <div class="relative z-10 mx-auto grid max-w-6xl gap-12 px-6 py-20 md:py-28 lg:grid-cols-2 lg:gap-16 lg:py-32">
            {{-- Left: pitch --}}
            <div class="flex flex-col justify-center">
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-300">
                    <span class="relative inline-flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
                    </span>
                    Power Dialer · Smart Queue · AI Call Intelligence
                </span>

                <h1 class="mt-6 text-5xl font-semibold tracking-tight text-white sm:text-6xl lg:text-7xl">
                    Dial faster.<br>
                    <span class="bg-gradient-to-r from-indigo-400 via-fuchsia-400 to-rose-400 bg-clip-text text-transparent">
                        Close more.
                    </span>
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-zinc-400 sm:text-xl">
                    The all-in-one phone for high-velocity sales teams. Smart queues route the next best call to your reps, AI listens for buying signals, and ringless voicemails warm prospects automatically.
                </p>

                <dl class="mt-10 grid grid-cols-3 gap-6 border-t border-zinc-800 pt-8 text-left">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-zinc-500">Calls / hour</dt>
                        <dd class="mt-1 text-2xl font-semibold text-white">120+</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-zinc-500">Connect rate</dt>
                        <dd class="mt-1 text-2xl font-semibold text-white">3×</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-zinc-500">Setup time</dt>
                        <dd class="mt-1 text-2xl font-semibold text-white">< 5 min</dd>
                    </div>
                </dl>
            </div>

            {{-- Right: embedded login card --}}
            <div class="flex items-center justify-center">
                <div class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8 shadow-2xl backdrop-blur">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-white">{{ __('Sign in to your dialer') }}</h2>
                        <p class="mt-1 text-sm text-zinc-400">{{ __('Welcome back. Time to make some calls.') }}</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-4 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-300">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-md border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
                        @csrf

                        <flux:input
                            name="email"
                            :label="__('Email address')"
                            :value="old('email')"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="email@example.com"
                        />

                        <flux:input
                            name="password"
                            :label="__('Password')"
                            type="password"
                            required
                            autocomplete="current-password"
                            :placeholder="__('Password')"
                            viewable
                        />

                        <div class="flex items-center justify-between">
                            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />
                            @if (Route::has('password.request'))
                                <flux:link class="text-sm" :href="route('password.request')">
                                    {{ __('Forgot password?') }}
                                </flux:link>
                            @endif
                        </div>

                        <flux:button variant="primary" type="submit" class="w-full" icon-trailing="arrow-right">
                            {{ __('Sign In') }}
                        </flux:button>
                    </form>

                    <p class="mt-6 text-center text-xs text-zinc-500">
                        {{ __('By signing in you agree to our acceptable use policy.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        Feature grid
        ────────────────────────────────────────────────────────── --}}
    <section class="border-t border-zinc-900 bg-zinc-950 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                    Everything you need to work the phones
                </h2>
                <p class="mt-4 text-lg text-zinc-400">
                    Three engines, one workflow. Configured the moment you sign up.
                </p>
            </div>

            <div class="mt-16 grid gap-8 md:grid-cols-3">
                {{-- Power Dialer --}}
                <div class="group rounded-2xl border border-zinc-800 bg-zinc-900/40 p-8 transition hover:border-indigo-500/40 hover:bg-zinc-900/80">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-400 ring-1 ring-indigo-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.272.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white">Power Dialer</h3>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-400">
                        Click one button to start a session. The smart queue feeds calls non-stop while your reps focus on selling, not dialing.
                    </p>
                </div>

                {{-- AI Call Intelligence --}}
                <div class="group rounded-2xl border border-zinc-800 bg-zinc-900/40 p-8 transition hover:border-fuchsia-500/40 hover:bg-zinc-900/80">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-fuchsia-500/10 text-fuchsia-400 ring-1 ring-fuchsia-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white">AI Call Intelligence</h3>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-400">
                        Every call is transcribed, summarized, and scored automatically. Surfaces objections, buying signals, and follow-ups so nothing slips.
                    </p>
                </div>

                {{-- Ringless Voicemail --}}
                <div class="group rounded-2xl border border-zinc-800 bg-zinc-900/40 p-8 transition hover:border-rose-500/40 hover:bg-zinc-900/80">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-rose-500/10 text-rose-400 ring-1 ring-rose-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-1.883 2.542l-7.5 1.207a2.25 2.25 0 0 1-.733 0l-7.5-1.207a2.25 2.25 0 0 1-1.884-2.542l1.215-9.94a2.25 2.25 0 0 1 1.95-1.964c1.95-.305 3.99-.456 6.069-.456 2.078 0 4.118.151 6.069.456a2.25 2.25 0 0 1 1.95 1.965l1.214 9.94Z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white">Ringless Voicemail</h3>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-400">
                        Drop voicemails to thousands of prospects without ringing their phone. Warm up your pipeline while your reps work live calls.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        Social proof slot — placeholder, hidden until we have content
        ────────────────────────────────────────────────────────── --}}
    @if (false)
    <section class="border-t border-zinc-900 bg-zinc-950 py-24">
        <div class="mx-auto max-w-6xl px-6 text-center">
            <p class="text-sm uppercase tracking-widest text-zinc-500">Trusted by sales teams everywhere</p>
            {{-- TODO: client logos & testimonials --}}
            <div class="mt-10 grid grid-cols-2 gap-8 opacity-60 md:grid-cols-5">
                {{-- placeholder logo cells --}}
            </div>
        </div>
    </section>
    @endif

    {{-- ──────────────────────────────────────────────────────────
        CTA strip
        ────────────────────────────────────────────────────────── --}}
    <section class="border-t border-zinc-900 bg-gradient-to-b from-zinc-950 to-zinc-900 py-20">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                Ready to dial?
            </h2>
            <p class="mt-4 text-lg text-zinc-400">
                Sign in and your queue is already waiting.
            </p>
            <div class="mt-8 flex justify-center">
                <flux:button :href="route('login')" variant="primary" icon-trailing="arrow-right">
                    {{ __('Sign In') }}
                </flux:button>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        Footer
        ────────────────────────────────────────────────────────── --}}
    <footer class="border-t border-zinc-900 bg-zinc-950 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 text-sm text-zinc-500 md:flex-row">
            <p>&copy; {{ date('Y') }} QuickSDR. All rights reserved.</p>
            <p>{{ __('Built for closers.') }}</p>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
