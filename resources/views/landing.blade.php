<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => __('QuickSDR — Multi-line dialer + AI for SDR teams & agencies')])

    {{-- SEO + Open Graph --}}
    <meta name="description" content="QuickSDR is a multi-line power dialer with AI call intelligence — run a dedicated SDR channel for your sales team or an inbound answering service for your clients. Dedicated server, no per-seat tax.">
    <meta property="og:title" content="QuickSDR — Multi-line dialer + AI for SDR teams & agencies">
    <meta property="og:description" content="Run a dedicated SDR channel that adds 35–50 deals per month per location, or offer phone answering as a new revenue layer for your client roster.">
    <meta property="og:image" content="/brand/qsdr-logo-light.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://quicksdr.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="QuickSDR — Multi-line dialer + AI for SDR teams & agencies">
    <meta name="twitter:description" content="Run a dedicated SDR channel, or offer phone answering as a new revenue layer.">
    <meta name="twitter:image" content="/brand/qsdr-logo-light.png">

    <style>
        html { scroll-behavior: smooth; }

        /* ──────────── Shared dark-band glow ──────────── */
        .qsdr-glow::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(60rem 40rem at 50% -10%, rgba(37, 99, 235, 0.30), transparent 60%),
                radial-gradient(40rem 30rem at 80% 30%, rgba(6, 182, 212, 0.18), transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* ──────────── 5-line dialer (hero) ──────────── */
        @keyframes qsdr-line-cycle {
            0%, 8%    { background-color: rgb(24 24 27);  border-color: rgb(39 39 42);  }
            10%, 40%  { background-color: rgb(8 47 73);   border-color: rgb(14 116 144); }
            42%, 75%  { background-color: rgb(5 46 22);   border-color: rgb(34 197 94); }
            77%, 100% { background-color: rgb(24 24 27);  border-color: rgb(39 39 42); }
        }
        @keyframes qsdr-line-voicemail {
            0%, 8%    { background-color: rgb(24 24 27);  border-color: rgb(39 39 42);  }
            10%, 40%  { background-color: rgb(8 47 73);   border-color: rgb(14 116 144); }
            42%, 75%  { background-color: rgb(39 39 42);  border-color: rgb(82 82 91); }
            77%, 100% { background-color: rgb(24 24 27);  border-color: rgb(39 39 42); }
        }
        @keyframes qsdr-dot-pulse {
            0%, 100% { transform: scale(1);   opacity: 0.6; }
            50%      { transform: scale(1.4); opacity: 1;   }
        }
        .qsdr-line          { animation: qsdr-line-cycle 6s ease-in-out infinite; }
        .qsdr-line-vm       { animation: qsdr-line-voicemail 6s ease-in-out infinite; }
        .qsdr-line-delay-1  { animation-delay: 0.4s; }
        .qsdr-line-delay-2  { animation-delay: 0.8s; }
        .qsdr-line-delay-3  { animation-delay: 1.2s; }
        .qsdr-dot           { animation: qsdr-dot-pulse 1.4s ease-in-out infinite; }

        @keyframes qsdr-label-connected {
            0%, 8%    { opacity: 0; }
            10%, 40%  { opacity: 0; }
            42%, 75%  { opacity: 1; }
            77%, 100% { opacity: 0; }
        }
        @keyframes qsdr-label-ringing {
            0%, 8%    { opacity: 0; }
            10%, 40%  { opacity: 1; }
            42%, 75%  { opacity: 0; }
            77%, 100% { opacity: 0; }
        }
        @keyframes qsdr-label-voicemail {
            0%, 8%    { opacity: 0; }
            10%, 40%  { opacity: 0; }
            42%, 75%  { opacity: 1; }
            77%, 100% { opacity: 0; }
        }
        @keyframes qsdr-label-idle {
            0%, 8%    { opacity: 1; }
            10%, 75%  { opacity: 0; }
            77%, 100% { opacity: 1; }
        }
        .qsdr-label-connected  { animation: qsdr-label-connected  6s ease-in-out infinite; }
        .qsdr-label-ringing    { animation: qsdr-label-ringing    6s ease-in-out infinite; }
        .qsdr-label-voicemail  { animation: qsdr-label-voicemail  6s ease-in-out infinite; }
        .qsdr-label-idle       { animation: qsdr-label-idle       6s ease-in-out infinite; }

        /* ──────────── AI scoring graphic ──────────── */
        @keyframes qsdr-score-fill {
            0%, 5%    { width: 0%; }
            30%       { width: 38%; }
            65%       { width: 72%; }
            90%, 100% { width: 87%; }
        }
        @keyframes qsdr-score-color {
            0%, 30%   { background-color: rgb(82 82 91); }
            45%, 70%  { background-color: rgb(6 182 212); }
            85%, 100% { background-color: rgb(16 185 129); }
        }
        .qsdr-score-bar      { animation: qsdr-score-fill 6s ease-out infinite; width: 0; }
        .qsdr-score-bar-fill { animation: qsdr-score-color 6s ease-out infinite; }

        @keyframes qsdr-fade-up {
            0%, 5%   { opacity: 0; transform: translateY(6px); }
            18%, 100% { opacity: 1; transform: translateY(0); }
        }
        .qsdr-fade-up    { animation: qsdr-fade-up 6s ease-out infinite; opacity: 0; }
        .qsdr-fade-d-1   { animation-delay: 0.0s; }
        .qsdr-fade-d-2   { animation-delay: 0.5s; }
        .qsdr-fade-d-3   { animation-delay: 1.1s; }
        .qsdr-fade-d-4   { animation-delay: 1.7s; }
        .qsdr-fade-d-5   { animation-delay: 2.4s; }
        .qsdr-fade-d-6   { animation-delay: 3.0s; }

        @keyframes qsdr-chip-pop {
            0%, 70%   { opacity: 0; transform: scale(0.92); }
            82%, 100% { opacity: 1; transform: scale(1); }
        }
        .qsdr-chip       { animation: qsdr-chip-pop 6s ease-out infinite; opacity: 0; }
        .qsdr-chip-d-1   { animation-delay: 0.0s; }
        .qsdr-chip-d-2   { animation-delay: 0.3s; }
        .qsdr-chip-d-3   { animation-delay: 0.6s; }

        /* ──────────── Inbound queue graphic ──────────── */
        @keyframes qsdr-queue-route {
            0%, 6%    { background-color: rgb(24 24 27); border-color: rgb(39 39 42); transform: translateX(-12px); opacity: 0; }
            12%, 30%  { background-color: rgb(24 24 27); border-color: rgb(39 39 42); transform: translateX(0); opacity: 1; }
            32%, 55%  { background-color: rgb(8 47 73);  border-color: rgb(14 116 144); transform: translateX(0); opacity: 1; }
            60%, 90%  { background-color: rgb(5 46 22);  border-color: rgb(34 197 94); transform: translateX(0); opacity: 1; }
            95%, 100% { background-color: rgb(5 46 22);  border-color: rgb(34 197 94); transform: translateX(0); opacity: 1; }
        }
        .qsdr-queue       { animation: qsdr-queue-route 6s ease-in-out infinite; opacity: 0; }
        .qsdr-queue-d-1   { animation-delay: 0.0s; }
        .qsdr-queue-d-2   { animation-delay: 0.6s; }
        .qsdr-queue-d-3   { animation-delay: 1.2s; }
        .qsdr-queue-d-4   { animation-delay: 1.8s; }

        @keyframes qsdr-queue-label-wait {
            0%, 30%   { opacity: 1; }
            32%, 100% { opacity: 0; }
        }
        @keyframes qsdr-queue-label-routing {
            0%, 30%   { opacity: 0; }
            32%, 55%  { opacity: 1; }
            60%, 100% { opacity: 0; }
        }
        @keyframes qsdr-queue-label-routed {
            0%, 55%   { opacity: 0; }
            60%, 100% { opacity: 1; }
        }
        .qsdr-q-wait    { animation: qsdr-queue-label-wait    6s ease-in-out infinite; }
        .qsdr-q-routing { animation: qsdr-queue-label-routing 6s ease-in-out infinite; }
        .qsdr-q-routed  { animation: qsdr-queue-label-routed  6s ease-in-out infinite; }

        /* ──────────── Mini IVR (use-case card) ──────────── */
        @keyframes qsdr-ivr-walk {
            0%, 24%   { background-color: rgb(24 24 27); border-color: rgb(63 63 70); color: rgb(161 161 170); }
            30%, 70%  { background-color: rgb(8 47 73);  border-color: rgb(14 116 144); color: rgb(165 243 252); }
            76%, 100% { background-color: rgb(24 24 27); border-color: rgb(63 63 70); color: rgb(161 161 170); }
        }
        .qsdr-ivr       { animation: qsdr-ivr-walk 6s ease-in-out infinite; }
        .qsdr-ivr-d-1   { animation-delay: 0.0s; }
        .qsdr-ivr-d-2   { animation-delay: 2.0s; }
        .qsdr-ivr-d-3   { animation-delay: 4.0s; }

        /* ──────────── Mini SDR pipeline (use-case card) ──────────── */
        @keyframes qsdr-pulse-soft {
            0%, 100% { transform: scale(1);    box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.40); }
            50%      { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(6, 182, 212, 0); }
        }
        .qsdr-pulse-soft { animation: qsdr-pulse-soft 2s ease-in-out infinite; }

        @keyframes qsdr-flow-arrow {
            0%, 100% { opacity: 0.3; transform: translateX(0); }
            50%      { opacity: 1;   transform: translateX(4px); }
        }
        .qsdr-flow-arrow { animation: qsdr-flow-arrow 2s ease-in-out infinite; }

        @media (prefers-reduced-motion: reduce) {
            .qsdr-line, .qsdr-line-vm, .qsdr-dot,
            .qsdr-label-connected, .qsdr-label-ringing,
            .qsdr-label-voicemail, .qsdr-label-idle,
            .qsdr-score-bar, .qsdr-score-bar-fill,
            .qsdr-fade-up, .qsdr-chip,
            .qsdr-queue, .qsdr-q-wait, .qsdr-q-routing, .qsdr-q-routed,
            .qsdr-ivr, .qsdr-pulse-soft, .qsdr-flow-arrow {
                animation: none;
                opacity: 1;
            }
            .qsdr-score-bar { width: 87%; }
        }
    </style>
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased">

    {{-- ──────────────────────────────────────────────────────────
        1. Sticky header (light)
        ────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-50 border-b border-zinc-200 bg-white/85 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="QuickSDR home">
                <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
            </a>

            <nav class="hidden items-center gap-7 text-sm text-zinc-600 md:flex" aria-label="Primary">
                <a href="{{ route('for-sales-teams') }}" class="transition hover:text-zinc-900">For Sales Teams</a>
                <a href="{{ route('for-agencies') }}" class="transition hover:text-zinc-900">For Agencies</a>
                <a href="#features" class="transition hover:text-zinc-900">Features</a>
                <a href="#pricing" class="transition hover:text-zinc-900">Pricing</a>
                <a href="#faq" class="transition hover:text-zinc-900">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                <flux:button href="/signup" variant="primary" size="sm" icon-trailing="arrow-right">
                    {{ __('Start free trial') }}
                </flux:button>
            </div>
        </div>
    </header>

    {{-- ──────────────────────────────────────────────────────────
        2. Hero — DARK showcase band with the 5-line dialer
        ────────────────────────────────────────────────────────── --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100">
        <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-6 py-20 md:py-24 lg:grid-cols-[1.2fr_1fr] lg:gap-16 lg:py-28">
            <div class="flex flex-col justify-center">
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-cyan-400/40 bg-cyan-500/10 px-3 py-1 text-xs font-medium text-cyan-200">
                    <span class="relative inline-flex h-2 w-2" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-400"></span>
                    </span>
                    For SDR teams, agencies &amp; operators
                </span>

                <h1 class="mt-6 text-5xl font-semibold tracking-tight sm:text-6xl lg:text-7xl">
                    One channel.<br>
                    <span class="bg-gradient-to-r from-blue-400 via-cyan-300 to-blue-300 bg-clip-text text-transparent">
                        One predictable result.
                    </span>
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-zinc-200 sm:text-xl">
                    Run a dedicated SDR channel that adds <strong class="text-cyan-200">35–50 deals per month per location</strong> — or stand up a phone-answering service your agency can resell to its client roster. Dedicated server. No per-seat tax. Zero CRM lock-in.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <flux:button href="/signup" variant="primary" icon-trailing="arrow-right">
                        {{ __('Start free trial') }}
                    </flux:button>
                    <flux:button href="#use-cases" variant="ghost">
                        {{ __('See use cases') }}
                    </flux:button>
                </div>

                <p class="mt-6 text-sm text-zinc-300">
                    3-day free trial &middot; No card required to demo &middot; TCPA-compliant out of the box
                </p>
            </div>

            <div class="flex items-center justify-center" aria-hidden="true">
                <div class="w-full max-w-sm rounded-2xl border border-zinc-800 bg-zinc-900/70 p-5 shadow-2xl backdrop-blur">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs font-medium text-zinc-200">
                            <span class="inline-flex h-2 w-2 rounded-full bg-cyan-400 qsdr-dot"></span>
                            Session active &middot; 5 lines
                        </div>
                        <span class="rounded-md bg-zinc-800 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-zinc-300">Live</span>
                    </div>

                    <div class="space-y-2.5">
                        <div class="qsdr-line rounded-lg border px-3 py-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono text-zinc-200">+1 (415) 555-0148</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing&hellip;</span>
                                    <span class="qsdr-label-connected text-emerald-300">Connected</span>
                                </span>
                            </div>
                            <div class="mt-1 text-[11px] text-zinc-500">Acme Corp &middot; VP Sales</div>
                        </div>

                        <div class="qsdr-line-vm qsdr-line-delay-1 rounded-lg border px-3 py-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono text-zinc-200">+1 (312) 555-0102</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing&hellip;</span>
                                    <span class="qsdr-label-voicemail text-zinc-400">Voicemail</span>
                                </span>
                            </div>
                            <div class="mt-1 text-[11px] text-zinc-500">Northbound Co &middot; Director</div>
                        </div>

                        <div class="qsdr-line qsdr-line-delay-2 rounded-lg border px-3 py-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono text-zinc-200">+1 (206) 555-0176</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing&hellip;</span>
                                    <span class="qsdr-label-connected text-emerald-300">Connected</span>
                                </span>
                            </div>
                            <div class="mt-1 text-[11px] text-zinc-500">Meridian Logistics &middot; Ops Lead</div>
                        </div>

                        <div class="qsdr-line-vm qsdr-line-delay-3 rounded-lg border px-3 py-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono text-zinc-200">+1 (617) 555-0193</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing&hellip;</span>
                                    <span class="qsdr-label-voicemail text-zinc-400">Voicemail</span>
                                </span>
                            </div>
                            <div class="mt-1 text-[11px] text-zinc-500">Helix Data &middot; Head of RevOps</div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-zinc-800 pt-3 text-[11px] text-zinc-400">
                        <span>First pickup auto-routes</span>
                        <span class="font-mono text-cyan-300">3.2s avg</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        3. Trust bar (light) — results-flavored stats
        ────────────────────────────────────────────────────────── --}}
    <section class="border-b border-zinc-200 bg-zinc-50" aria-label="Key statistics">
        <div class="mx-auto max-w-6xl px-6 py-10">
            <dl class="grid grid-cols-2 gap-6 md:grid-cols-5">
                @foreach ([
                    ['+35–50', 'deals/mo per location'],
                    ['5 lines', 'parallel · 3.2s avg pickup'],
                    ['$0', 'per-seat fees, ever'],
                    ['Unlimited', 'agents + companies'],
                    ['TCPA + A2P', 'compliant out of the box'],
                ] as [$stat, $label])
                    <div class="text-center">
                        <dt class="bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-2xl font-semibold tracking-tight text-transparent sm:text-3xl">
                            {{ $stat }}
                        </dt>
                        <dd class="mt-1 text-xs uppercase tracking-wider text-zinc-500">{{ $label }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        4. Who this is for — two-up use-case cards (light)
        ────────────────────────────────────────────────────────── --}}
    <section id="use-cases" class="bg-white py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">
                    Two ways to put a dialer to work.
                </h2>
                <p class="mt-4 text-lg text-zinc-600">
                    Pick the use case that matches your business — both run on the same platform.
                </p>
            </div>

            <div class="mt-14 grid gap-6 lg:grid-cols-2">
                <a href="{{ route('for-sales-teams') }}" class="group relative flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 transition hover:-translate-y-0.5 hover:border-cyan-400/60 hover:shadow-xl">
                    <span class="text-xs font-semibold uppercase tracking-widest text-cyan-600">For sales teams &amp; operators</span>
                    <h3 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-900">
                        Build a dedicated SDR channel.
                    </h3>
                    <p class="mt-3 text-zinc-600">
                        Your sales floor is already stretched. One BDR with a clean channel — focused on a single bottleneck like inventory acquisition, lead recovery, or follow-ups — adds <strong class="text-zinc-900">35–50 deals per month per location</strong> without touching the team you have.
                    </p>

                    <div class="mt-7 flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-4" aria-hidden="true">
                        <div class="flex flex-col gap-1.5 text-[11px]">
                            <span class="rounded-md bg-zinc-100 px-2 py-1 text-zinc-700">Online offer</span>
                            <span class="rounded-md bg-zinc-100 px-2 py-1 text-zinc-700">Walk-in</span>
                            <span class="rounded-md bg-zinc-100 px-2 py-1 text-zinc-700">Follow-up</span>
                        </div>
                        <flux:icon name="arrow-right" class="qsdr-flow-arrow h-4 w-4 text-cyan-500" />
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-xs font-semibold text-white shadow-md qsdr-pulse-soft">
                            BDR
                        </div>
                        <flux:icon name="arrow-right" class="qsdr-flow-arrow h-4 w-4 text-cyan-500" />
                        <div class="flex flex-col items-end gap-1">
                            <span class="font-mono text-2xl font-bold text-emerald-600">+12</span>
                            <span class="text-[10px] uppercase tracking-wider text-zinc-500">deals this wk</span>
                        </div>
                    </div>

                    <span class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-cyan-700 group-hover:text-cyan-600">
                        Read the playbook
                        <flux:icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                    </span>
                </a>

                <a href="{{ route('for-agencies') }}" class="group relative flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 transition hover:-translate-y-0.5 hover:border-cyan-400/60 hover:shadow-xl">
                    <span class="text-xs font-semibold uppercase tracking-widest text-cyan-600">For agencies, VAs &amp; CMS</span>
                    <h3 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-900">
                        Resell phone answering as a service.
                    </h3>
                    <p class="mt-3 text-zinc-600">
                        You already do their bookkeeping, scheduling, or admin — phone answering is the next layer. Stand up an inbound service for your clients <strong class="text-zinc-900">without hiring a phone team</strong>, then bill it on top of what you already charge.
                    </p>

                    <div class="mt-7 rounded-xl border border-zinc-200 bg-white p-4" aria-hidden="true">
                        <div class="mb-3 flex items-center gap-2 text-[11px] text-zinc-500">
                            <flux:icon name="phone-arrow-down-left" class="h-3.5 w-3.5 text-cyan-500" />
                            Inbound call · Acme Realty
                        </div>
                        <div class="space-y-1.5">
                            <div class="qsdr-ivr qsdr-ivr-d-1 flex items-center justify-between rounded-md border px-3 py-1.5 text-[11px]">
                                <span>Press 1 — New listings</span>
                                <span class="text-[10px] uppercase tracking-wider">Routed</span>
                            </div>
                            <div class="qsdr-ivr qsdr-ivr-d-2 flex items-center justify-between rounded-md border px-3 py-1.5 text-[11px]">
                                <span>Press 2 — Existing clients</span>
                                <span class="text-[10px] uppercase tracking-wider">Routed</span>
                            </div>
                            <div class="qsdr-ivr qsdr-ivr-d-3 flex items-center justify-between rounded-md border px-3 py-1.5 text-[11px]">
                                <span>Press 3 — Owner direct</span>
                                <span class="text-[10px] uppercase tracking-wider">Routed</span>
                            </div>
                        </div>
                    </div>

                    <span class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-cyan-700 group-hover:text-cyan-600">
                        See the agency playbook
                        <flux:icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                    </span>
                </a>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        5a. Feature showcase — Multi-line dialer (DARK)
        ────────────────────────────────────────────────────────── --}}
    <section id="features" class="dark qsdr-glow relative overflow-hidden bg-zinc-950 py-24 text-zinc-100">
        <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-300">Power Dialer</span>
                <h2 class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl">
                    Five lines. Zero dead air.
                </h2>
                <p class="mt-4 text-lg text-zinc-300">
                    Parallel-dial five numbers at once. The first human pickup auto-routes to your rep. Voicemails and dead lines drop before anyone says hello — no double-connects, no awkward apologies.
                </p>
                <ul class="mt-6 space-y-3">
                    @foreach ([
                        'Zero-latency handoff — muted-join means the prospect hears you the moment you pick up.',
                        'Automatic throttle backs off before TCPA abandonment-rate triggers.',
                        'One-click voicemail drops — leave a pre-recorded message without ringing the phone.',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <flux:icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-cyan-300" />
                            <span class="text-zinc-200">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="flex items-center justify-center" aria-hidden="true">
                <div class="w-full max-w-xs rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4 shadow-2xl backdrop-blur">
                    <div class="mb-3 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 font-medium text-zinc-200">
                            <span class="inline-flex h-2 w-2 rounded-full bg-cyan-400 qsdr-dot"></span>
                            5 lines
                        </div>
                        <span class="font-mono text-cyan-300">3.2s</span>
                    </div>
                    <div class="space-y-2">
                        <div class="qsdr-line rounded-md border px-2.5 py-2 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-zinc-200">+1 (415) 555-0148</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing</span>
                                    <span class="qsdr-label-connected text-emerald-300">Connected</span>
                                </span>
                            </div>
                        </div>
                        <div class="qsdr-line-vm qsdr-line-delay-1 rounded-md border px-2.5 py-2 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-zinc-200">+1 (312) 555-0102</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing</span>
                                    <span class="qsdr-label-voicemail text-zinc-400">VM</span>
                                </span>
                            </div>
                        </div>
                        <div class="qsdr-line qsdr-line-delay-2 rounded-md border px-2.5 py-2 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-zinc-200">+1 (206) 555-0176</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing</span>
                                    <span class="qsdr-label-connected text-emerald-300">Connected</span>
                                </span>
                            </div>
                        </div>
                        <div class="qsdr-line-vm qsdr-line-delay-3 rounded-md border px-2.5 py-2 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-zinc-200">+1 (617) 555-0193</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing</span>
                                    <span class="qsdr-label-voicemail text-zinc-400">VM</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        5b. Feature showcase — AI Call Intelligence (DARK)
        ────────────────────────────────────────────────────────── --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-zinc-950 py-24 text-zinc-100">
        <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <div class="order-2 flex items-center justify-center lg:order-1" aria-hidden="true">
                <div class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900/70 p-5 shadow-2xl backdrop-blur">
                    <div class="mb-3 flex items-center justify-between text-xs">
                        <span class="flex items-center gap-2 font-medium text-zinc-200">
                            <flux:icon name="sparkles" class="h-4 w-4 text-cyan-300" />
                            Live transcript
                        </span>
                        <span class="font-mono text-zinc-400">00:42</span>
                    </div>

                    <div class="space-y-2 text-[12px] leading-snug text-zinc-300">
                        <div class="qsdr-fade-up qsdr-fade-d-1 rounded-md bg-zinc-800/60 px-3 py-1.5">
                            <span class="font-semibold text-zinc-100">Rep:</span> Quick question about your acquisition pipeline…
                        </div>
                        <div class="qsdr-fade-up qsdr-fade-d-2 rounded-md bg-cyan-500/10 px-3 py-1.5 ring-1 ring-cyan-500/20">
                            <span class="font-semibold text-cyan-200">Prospect:</span> Yeah, we're stretched thin. We've been thinking about hiring.
                        </div>
                        <div class="qsdr-fade-up qsdr-fade-d-3 rounded-md bg-zinc-800/60 px-3 py-1.5">
                            <span class="font-semibold text-zinc-100">Rep:</span> Most teams skip a step before hiring — what's the bottleneck right now?
                        </div>
                        <div class="qsdr-fade-up qsdr-fade-d-4 rounded-md bg-cyan-500/10 px-3 py-1.5 ring-1 ring-cyan-500/20">
                            <span class="font-semibold text-cyan-200">Prospect:</span> Honestly? Our follow-ups. Leads going cold.
                        </div>
                    </div>

                    <div class="mt-5 border-t border-zinc-800 pt-4">
                        <div class="flex items-center justify-between text-[11px] text-zinc-400">
                            <span class="uppercase tracking-wider">Lead score</span>
                            <span class="qsdr-fade-up qsdr-fade-d-5 font-mono text-base font-semibold text-emerald-300">87 / 100</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-800">
                            <div class="qsdr-score-bar qsdr-score-bar-fill h-full rounded-full"></div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="qsdr-chip qsdr-chip-d-1 inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-1 text-[11px] font-medium text-emerald-200 ring-1 ring-emerald-500/30">
                            <flux:icon name="check" class="h-3 w-3" /> Interested
                        </span>
                        <span class="qsdr-chip qsdr-chip-d-2 inline-flex items-center gap-1 rounded-full bg-cyan-500/15 px-2.5 py-1 text-[11px] font-medium text-cyan-200 ring-1 ring-cyan-500/30">
                            Pain: Follow-ups
                        </span>
                        <span class="qsdr-chip qsdr-chip-d-3 inline-flex items-center gap-1 rounded-full bg-blue-500/15 px-2.5 py-1 text-[11px] font-medium text-blue-200 ring-1 ring-blue-500/30">
                            Next: Demo Tue
                        </span>
                    </div>
                </div>
            </div>

            <div class="order-1 lg:order-2">
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-300">AI Call Intelligence</span>
                <h2 class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl">
                    Know which calls mattered — before you hang up.
                </h2>
                <p class="mt-4 text-lg text-zinc-300">
                    Real-time transcription streams into a scoring model tuned for B2B outbound. Lead score, pain flags, and next-step suggestions land on your rep's screen <em>during</em> the call — not after.
                </p>
                <ul class="mt-6 space-y-3">
                    @foreach ([
                        'Bring your own keys — OpenAI, Anthropic, Google, or any OpenAI-compatible endpoint.',
                        'Auto-summary drops into your CRM timeline. No post-call note-taking.',
                        'Lead scoring ranks every conversation so reps know who to call back first.',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <flux:icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-cyan-300" />
                            <span class="text-zinc-200">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        5c. Feature showcase — Smart Inbound Queue (DARK)
        ────────────────────────────────────────────────────────── --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-zinc-950 py-24 text-zinc-100">
        <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-300">Smart Inbound Queue</span>
                <h2 class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl">
                    Every inbound call, routed before it rings twice.
                </h2>
                <p class="mt-4 text-lg text-zinc-300">
                    IVR-aware queue distributes inbound calls across your available agents in parallel. No "please hold." No missed callbacks. The queue tracks per-agent availability live and never double-routes the same caller.
                </p>
                <ul class="mt-6 space-y-3">
                    @foreach ([
                        'Per-client / per-campaign routing — each tenant gets its own greeting and queue.',
                        'Per-timezone calling windows automatically enforce TCPA hours.',
                        'Missed-call recovery — inbound from a previously-dialed lead jumps the queue.',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <flux:icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-cyan-300" />
                            <span class="text-zinc-200">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="flex items-center justify-center" aria-hidden="true">
                <div class="w-full max-w-sm rounded-2xl border border-zinc-800 bg-zinc-900/70 p-5 shadow-2xl backdrop-blur">
                    <div class="mb-3 flex items-center justify-between text-xs">
                        <span class="flex items-center gap-2 font-medium text-zinc-200">
                            <flux:icon name="queue-list" class="h-4 w-4 text-cyan-300" />
                            Inbound queue · 3 agents
                        </span>
                        <span class="font-mono text-cyan-300">&lt;5s</span>
                    </div>

                    <div class="space-y-2">
                        @foreach ([
                            ['+1 (415) 555-0911', 'Acme Realty', 'd-1', 'A'],
                            ['+1 (312) 555-0244', 'Helix Data',  'd-2', 'B'],
                            ['+1 (206) 555-0177', 'Northbound',  'd-3', 'C'],
                            ['+1 (617) 555-0382', 'Meridian',    'd-4', 'A'],
                        ] as [$num, $co, $delay, $agent])
                            <div class="qsdr-queue qsdr-queue-{{ $delay }} rounded-md border px-3 py-2">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-mono text-zinc-200">{{ $num }}</span>
                                    <span class="relative font-medium">
                                        <span class="qsdr-q-wait absolute inset-0 right-0 text-right text-zinc-400">Waiting</span>
                                        <span class="qsdr-q-routing absolute inset-0 right-0 text-right text-cyan-300">Routing&hellip;</span>
                                        <span class="qsdr-q-routed text-emerald-300">→ Agent {{ $agent }}</span>
                                    </span>
                                </div>
                                <div class="mt-0.5 text-[10px] text-zinc-500">{{ $co }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-zinc-800 pt-3 text-[11px] text-zinc-400">
                        <span>Parallel routing</span>
                        <span class="font-mono text-cyan-300">0 missed</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        6. How it works (light)
        ────────────────────────────────────────────────────────── --}}
    <section class="bg-zinc-50 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">From signup to first dial in a day.</h2>
                <p class="mt-4 text-lg text-zinc-600">
                    Self-serve if you're already on Twilio — or pick a setup tier and we design the channel with you.
                </p>
            </div>

            <div class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['1', 'Pick your tier',          'Self-serve and plug in your own keys, or pick Tier 2/3 and we sit down with you to design the channel for your specific use case.'],
                    ['2', 'Stand up your channel',   'Per-client routing or per-location campaigns. Add unlimited agents and companies — your bill never moves.'],
                    ['3', 'Hit dial / route inbound','Your reps work pipeline. QuickSDR handles routing, compliance, AI scoring, and pipes data wherever your CRM needs it.'],
                ] as [$n, $title, $desc])
                    <div>
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 via-cyan-500 to-blue-500 text-lg font-bold text-white shadow-md shadow-cyan-500/20">
                            {{ $n }}
                        </div>
                        <h3 class="mt-5 text-xl font-semibold text-zinc-900">{{ $title }}</h3>
                        <p class="mt-2 text-zinc-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        7. Comparison (light)
        ────────────────────────────────────────────────────────── --}}
    <section class="bg-white py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">How we stack up.</h2>
                <p class="mt-4 text-lg text-zinc-600">
                    An honest comparison. If manual dialing is working for you, stay — we'd rather lose the deal than oversell it.
                </p>
            </div>

            @php
                $rows = [
                    ['Dials per rep per hour',       '30–40',                                       '80–120',                                            '300+'],
                    ['Per-agent fee',                'N/A',                                         '$80–$150 / user / mo',                              ['badge', 'yes', 'None — flat price']],
                    ['Multi-company / per-client',   ['badge', 'missing', '—'],                     ['badge', 'missing', 'Per-brand fees'],              ['badge', 'yes', 'Unlimited']],
                    ['AI call scoring',              ['badge', 'missing', '—'],                     ['badge', 'missing', '—'],                           ['badge', 'yes', 'Built in']],
                    ['Dedicated server',             ['badge', 'missing', '—'],                     ['badge', 'missing', 'Shared tenant'],               ['badge', 'yes', 'Single-tenant']],
                    ['CRM lock-in',                  '—',                                           ['badge', 'partial', 'Your CRM owns data'],          ['badge', 'yes', 'Works with any / standalone']],
                ];
            @endphp

            <div class="mt-12 hidden overflow-hidden rounded-2xl border border-zinc-200 md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th class="px-6 py-4 font-medium">Feature</th>
                            <th class="px-6 py-4 font-medium">Manual dialing</th>
                            <th class="px-6 py-4 font-medium">Single-line dialer</th>
                            <th class="bg-cyan-50 px-6 py-4 font-medium text-cyan-700">QuickSDR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">{{ $row[0] }}</td>
                                @foreach ([$row[1], $row[2], $row[3]] as $i => $cell)
                                    <td class="px-6 py-4 {{ $i === 2 ? 'bg-cyan-50' : '' }}">
                                        @if (is_array($cell) && $cell[0] === 'badge')
                                            @if ($cell[1] === 'yes')
                                                <flux:badge variant="success" size="sm" icon="check">{{ $cell[2] }}</flux:badge>
                                            @elseif ($cell[1] === 'partial')
                                                <flux:badge color="zinc" size="sm">{{ $cell[2] }}</flux:badge>
                                            @else
                                                <span class="text-zinc-400">{{ $cell[2] }}</span>
                                            @endif
                                        @else
                                            <span class="{{ $i === 2 ? 'font-semibold text-cyan-800' : 'text-zinc-700' }}">{{ $cell }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-10 space-y-4 md:hidden">
                @foreach ($rows as $row)
                    <div class="rounded-xl border border-zinc-200 bg-white p-5">
                        <div class="text-sm font-semibold text-zinc-900">{{ $row[0] }}</div>
                        <dl class="mt-3 grid grid-cols-1 gap-2 text-sm">
                            @foreach (['Manual' => $row[1], 'Single-line' => $row[2], 'QuickSDR' => $row[3]] as $label => $cell)
                                <div class="flex items-start justify-between gap-3 {{ $label === 'QuickSDR' ? 'rounded-md bg-cyan-50 px-2 py-1' : '' }}">
                                    <dt class="text-xs uppercase tracking-wider text-zinc-500">{{ $label }}</dt>
                                    <dd class="text-right">
                                        @if (is_array($cell) && $cell[0] === 'badge')
                                            @if ($cell[1] === 'yes')
                                                <flux:badge variant="success" size="sm" icon="check">{{ $cell[2] }}</flux:badge>
                                            @elseif ($cell[1] === 'partial')
                                                <flux:badge color="zinc" size="sm">{{ $cell[2] }}</flux:badge>
                                            @else
                                                <span class="text-zinc-400">{{ $cell[2] }}</span>
                                            @endif
                                        @else
                                            <span class="{{ $label === 'QuickSDR' ? 'font-semibold text-cyan-800' : 'text-zinc-700' }}">{{ $cell }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        8. Pricing (light)
        ────────────────────────────────────────────────────────── --}}
    <section id="pricing" class="bg-zinc-50 py-24">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">Pricing built around your channel.</h2>
                <p class="mt-4 text-lg text-zinc-600">
                    Every tier ships on a dedicated, single-tenant server. The setup tiers are about implementation strategy — we sit down with you, identify the channel that'll move the needle, and stand it up for your specific use case.
                </p>
            </div>

            <div class="mt-16 grid gap-6 lg:grid-cols-4">
                <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900">Self-serve</h3>
                        <p class="mt-1 text-sm text-zinc-600">For a solo closer or tight team ready to plug in their own keys.</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-bold text-zinc-900">$350</span>
                        <span class="text-zinc-600">/ month</span>
                    </div>
                    <ul class="mt-6 flex-1 space-y-3 text-sm">
                        @foreach ([
                            'Dedicated single-tenant server',
                            'Connect your own Twilio + AI keys',
                            '5-line parallel dialer',
                            'AI transcription &amp; scoring',
                            'Email support',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-700">
                                <flux:icon name="check" class="mt-0.5 h-4 w-4 flex-none text-cyan-600" />
                                {!! $bullet !!}
                            </li>
                        @endforeach
                    </ul>
                    <flux:button href="/signup?tier=1" variant="primary" class="mt-8 w-full" icon-trailing="arrow-right">
                        Start free trial
                    </flux:button>
                </div>

                <div class="relative flex flex-col rounded-2xl border-2 border-cyan-500 bg-white p-8 shadow-xl shadow-cyan-500/15 lg:scale-[1.03]">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white shadow">
                            Most Popular
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900">Setup + Use-Case Consultation</h3>
                        <p class="mt-1 text-sm text-zinc-600">We sit down with you, identify the channel that'll move the needle, and stand it up — so you launch on day one with the right setup for <em>your</em> use case.</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-bold text-zinc-900">$1,000</span>
                        <span class="text-zinc-600"> setup</span>
                        <div class="mt-1 text-sm text-zinc-600">+ $350/mo &middot; <span class="font-semibold text-cyan-700">first 2 months waived</span></div>
                    </div>
                    <ul class="mt-6 flex-1 space-y-3 text-sm">
                        @foreach ([
                            'Everything in Self-serve',
                            '90-min strategy call to identify your bottleneck channel',
                            'Twilio + AI configured for <em>your</em> use case',
                            'Call flows + IVR designed for your customers',
                            'Priority support + Slack channel',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-700">
                                <flux:icon name="check" class="mt-0.5 h-4 w-4 flex-none text-cyan-600" />
                                <span>{!! $bullet !!}</span>
                            </li>
                        @endforeach
                    </ul>
                    <flux:button href="/signup?tier=2" variant="primary" class="mt-8 w-full" icon-trailing="arrow-right">
                        Book your strategy call
                    </flux:button>
                    <p class="mt-3 text-center text-xs italic text-zinc-500">
                        Matt personally runs every Tier 2 strategy call.
                    </p>
                </div>

                <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900">Multi-Location + Strategy Partnership</h3>
                        <p class="mt-1 text-sm text-zinc-600">Multiple locations or use cases. We architect, train, and stay engaged — so each location runs its own predictable channel from week one.</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-bold text-zinc-900">$3,000</span>
                        <span class="text-zinc-600"> setup</span>
                        <div class="mt-1 text-sm text-zinc-600">+ $350/mo &middot; <span class="font-semibold text-cyan-700">first 2 months waived</span></div>
                    </div>
                    <ul class="mt-6 flex-1 space-y-3 text-sm">
                        @foreach ([
                            'Everything in Setup + Use-Case Consultation',
                            'Multi-location / multi-use-case architecture',
                            'Live SDR + manager training per location',
                            'Monthly strategy session for the first 90 days',
                            'Upgraded server tier + priority support',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-700">
                                <flux:icon name="check" class="mt-0.5 h-4 w-4 flex-none text-cyan-600" />
                                {{ $bullet }}
                            </li>
                        @endforeach
                    </ul>
                    <flux:button href="/signup?tier=3" variant="primary" class="mt-8 w-full" icon-trailing="arrow-right">
                        Book a strategy call
                    </flux:button>
                    <p class="mt-3 text-center text-xs italic text-zinc-500">
                        Includes ongoing strategy access for your first 90 days.
                    </p>
                </div>

                <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900">Custom build</h3>
                        <p class="mt-1 text-sm text-zinc-600">Custom AI agents, deep integrations, and engineering on demand.</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-bold text-zinc-900">$160</span>
                        <span class="text-zinc-600">/ hour</span>
                    </div>
                    <ul class="mt-6 flex-1 space-y-3 text-sm">
                        @foreach ([
                            'Custom AI call agents',
                            'Custom CRM &amp; tooling integrations',
                            'Hourly engineering against your backlog',
                            'Architecture + security review',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-700">
                                <flux:icon name="check" class="mt-0.5 h-4 w-4 flex-none text-cyan-600" />
                                {!! $bullet !!}
                            </li>
                        @endforeach
                    </ul>
                    <flux:button href="/contact-sales" variant="primary" class="mt-8 w-full" icon-trailing="arrow-right">
                        Talk to sales
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        9. Testimonials (light)
        ────────────────────────────────────────────────────────── --}}
    <section class="bg-white py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">Teams are dialing smarter.</h2>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['JK', 'Jamie K.',  'Head of Sales, FinTech SaaS', 'Our 3-person team dials like a 10-person team. Pipeline is 4× higher in six weeks.'],
                    ['TR', 'Taylor R.', 'SDR Lead, B2B Services',      'The AI scoring means we stop wasting time on tire-kickers. Period.'],
                    ['MH', 'Morgan H.', 'VP Sales, Logistics Co.',     'Setup took a morning. We were live dialing by lunch.'],
                ] as [$initials, $name, $title, $quote])
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
                        <span class="text-5xl leading-none text-cyan-500" aria-hidden="true">&ldquo;</span>
                        <p class="mt-2 text-zinc-800">{{ $quote }}</p>
                        <div class="mt-6 flex items-center gap-3 border-t border-zinc-200 pt-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-semibold text-white" aria-hidden="true">
                                {{ $initials }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-zinc-900">{{ $name }}</div>
                                <div class="text-xs text-zinc-500">{{ $title }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        10. FAQ (light)
        ────────────────────────────────────────────────────────── --}}
    <section id="faq" class="bg-zinc-50 py-24">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">Frequently asked questions.</h2>
                <p class="mt-4 text-lg text-zinc-600">
                    Can't find what you need? <a href="/contact-sales" class="font-semibold text-cyan-700 hover:text-cyan-600">Ask us directly.</a>
                </p>
            </div>

            <div class="mt-12 divide-y divide-zinc-200 rounded-2xl border border-zinc-200 bg-white shadow-sm">
                @foreach ([
                    ['How is this different from Aircall, JustCall, or Outreach?',
                     "Those tools are great single-line dialers on a shared SaaS tenant. QuickSDR is a 5-line parallel dialer on a dedicated server that's yours, with AI call intelligence built in — not bolted on. You own your data, your numbers, and your deliverability reputation."],
                    ['What does multi-line dialing actually mean?',
                     'When your rep starts a session, QuickSDR rings up to 5 numbers simultaneously. The first human who picks up is routed to your rep instantly. The other calls drop before the prospect can even say hello — no double-connects, no awkward apologies.'],
                    ['Can I run this for multiple clients (agency / VA model)?',
                     'Yes — that\'s exactly what the per-client routing is for. Each client gets their own greeting, their own queue, and their own number. You bill them; they get phone answering they couldn\'t afford to staff. See the For Agencies page for the full playbook.'],
                    ['How does the dedicated SDR channel work?',
                     'The idea is to stop forcing your existing sales team to add another responsibility. Hire one person whose only job is acquisition (or follow-ups, or whichever channel is bottlenecked), and give them a clean dialer + queue. Locations using this approach add 35–50 deals a month per location. See the For Sales Teams page for the full breakdown.'],
                    ['How long does setup take?',
                     "Tier 1 is quick — connect your own Twilio + AI keys and dial. Tier 2 + 3 include a strategy call where we identify your bottleneck channel and design the call flows / routing for your specific use case — usually live within a week."],
                    ['Do I pay per agent or per company?',
                     "Neither. QuickSDR is flat per month per server — unlimited agents, unlimited companies, your bill never moves. If you're running multiple brands or client tenants, this is the biggest single saving vs. per-seat tools."],
                    ['Will I get flagged as spam?',
                     'Every outbound carrier has spam-flag heuristics. QuickSDR fights them proactively: automatic abandonment-rate throttling, TCPA window enforcement per timezone, AMD whitelisting, and local-presence rotation if you enable it.'],
                    ['Can I export my data if I cancel?',
                     'Always. Contacts, call logs, transcripts, recordings — all exportable for 7 days after cancellation. No lock-in, no "contact your account manager" runaround.'],
                ] as [$question, $answer])
                    <details class="group px-6 py-5 [&[open]]:bg-zinc-50">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-zinc-900 outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/60 focus-visible:ring-offset-2 focus-visible:ring-offset-white rounded">
                            <span class="font-medium">{{ $question }}</span>
                            <flux:icon name="chevron-down" class="h-5 w-5 flex-none text-zinc-400 transition group-open:rotate-180 group-open:text-cyan-600" />
                        </summary>
                        <div class="mt-3 text-zinc-600">{{ $answer }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        11. Final CTA (DARK)
        ────────────────────────────────────────────────────────── --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-gradient-to-r from-blue-700 via-blue-600 to-cyan-500 py-20">
        <div class="relative z-10 mx-auto max-w-4xl px-6 text-center">
            <h2 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                Build the channel you've been waiting on.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-white/95">
                Unlimited agents, unlimited companies, dedicated server. Pick the use case that fits your business — we'll help you stand it up.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="/signup"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-blue-700 shadow-xl transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50">
                    Start free trial
                    <flux:icon name="arrow-right" class="h-5 w-5" />
                </a>
                <a href="{{ route('for-sales-teams') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-8 py-4 text-base font-semibold text-white backdrop-blur transition hover:bg-white/20 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50">
                    Read the SDR playbook
                </a>
            </div>
            <p class="mt-6 text-sm text-white/85">
                3-day free trial &middot; No card required to demo &middot; Cancel anytime — your data stays exportable for 7 days.
            </p>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        12. Footer (light)
        ────────────────────────────────────────────────────────── --}}
    <footer class="border-t border-zinc-200 bg-zinc-100 py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-10 md:grid-cols-4">
                <div class="md:col-span-2">
                    <a href="{{ route('home') }}" class="inline-block" aria-label="QuickSDR home">
                        <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
                    </a>
                    <p class="mt-4 max-w-xs text-sm text-zinc-600">
                        Multi-line power dialer + AI call intelligence on a dedicated server. Build a channel that works.
                    </p>
                    <p class="mt-6 text-xs text-zinc-500">&copy; {{ date('Y') }} QuickSDR. All rights reserved.</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-700">Use cases</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('for-sales-teams') }}" class="text-zinc-600 transition hover:text-zinc-900">For Sales Teams</a></li>
                        <li><a href="{{ route('for-agencies') }}" class="text-zinc-600 transition hover:text-zinc-900">For Agencies &amp; VAs</a></li>
                        <li><a href="#features" class="text-zinc-600 transition hover:text-zinc-900">Features</a></li>
                        <li><a href="#pricing" class="text-zinc-600 transition hover:text-zinc-900">Pricing</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-700">Company</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="/about" class="text-zinc-600 transition hover:text-zinc-900">About</a></li>
                        <li><a href="/contact" class="text-zinc-600 transition hover:text-zinc-900">Contact</a></li>
                        <li><a href="/contact-sales" class="text-zinc-600 transition hover:text-zinc-900">Talk to sales</a></li>
                        <li><a href="#faq" class="text-zinc-600 transition hover:text-zinc-900">FAQ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
