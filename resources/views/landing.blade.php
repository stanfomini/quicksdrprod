<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    @include('partials.head', ['title' => __('QuickSDR — Multi-line dialer + AI for SDR teams')])

    {{-- SEO + Open Graph --}}
    <meta name="description" content="QuickSDR is a multi-line power dialer for SDR teams. Dial 5 lines in parallel, score every call with AI, and keep your cadence TCPA-compliant — on a dedicated server you own.">
    <meta property="og:title" content="QuickSDR — Multi-line dialer + AI for SDR teams">
    <meta property="og:description" content="Dial 5 lines at once. Close more this week. Built for sales managers running SDR teams.">
    <meta property="og:image" content="/brand/qsdr-logo-light.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://quicksdr.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="QuickSDR — Multi-line dialer + AI for SDR teams">
    <meta name="twitter:description" content="Dial 5 lines at once. Close more this week.">
    <meta name="twitter:image" content="/brand/qsdr-logo-light.png">

    <style>
        html { scroll-behavior: smooth; }

        /* Hero background glow — logo-aligned blue/cyan */
        .hero-glow::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(60rem 40rem at 50% -10%, rgba(37, 99, 235, 0.22), transparent 60%),
                radial-gradient(40rem 30rem at 80% 30%, rgba(6, 182, 212, 0.14), transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* Animated 4-line dialer mockup.
           Each line cycles: idle → ringing → connected/voicemail → idle,
           with a staggered delay so the viewer sees a realistic parallel dial. */
        @keyframes qsdr-line-cycle {
            0%, 8%    { background-color: rgb(24 24 27);  border-color: rgb(39 39 42);  }   /* idle */
            10%, 40%  { background-color: rgb(8 47 73);   border-color: rgb(14 116 144); }  /* ringing */
            42%, 75%  { background-color: rgb(5 46 22);   border-color: rgb(34 197 94); }   /* connected */
            77%, 100% { background-color: rgb(24 24 27);  border-color: rgb(39 39 42); }    /* idle again */
        }
        @keyframes qsdr-line-voicemail {
            0%, 8%    { background-color: rgb(24 24 27);  border-color: rgb(39 39 42);  }
            10%, 40%  { background-color: rgb(8 47 73);   border-color: rgb(14 116 144); }
            42%, 75%  { background-color: rgb(39 39 42);  border-color: rgb(82 82 91); }    /* voicemail = dim */
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

        /* Label fades tied to the same cycle so text matches color state. */
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

        @media (prefers-reduced-motion: reduce) {
            .qsdr-line, .qsdr-line-vm, .qsdr-dot,
            .qsdr-label-connected, .qsdr-label-ringing,
            .qsdr-label-voicemail, .qsdr-label-idle {
                animation: none;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">

    {{-- ──────────────────────────────────────────────────────────
        1. Sticky header
        ────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-50 border-b border-zinc-900/80 bg-zinc-950/70 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="QuickSDR home">
                <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
            </a>

            <nav class="hidden items-center gap-8 text-sm text-zinc-400 md:flex" aria-label="Primary">
                <a href="#features" class="hover:text-zinc-100 transition">Features</a>
                <a href="#pricing" class="hover:text-zinc-100 transition">Pricing</a>
                <a href="#faq" class="hover:text-zinc-100 transition">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                <flux:button href="/signup" variant="primary" size="sm" icon-trailing="arrow-right">
                    {{ __('Start free trial') }}
                </flux:button>
            </div>
        </div>
    </header>

    {{-- ──────────────────────────────────────────────────────────
        2. Hero (split, animated)
        ────────────────────────────────────────────────────────── --}}
    <section class="hero-glow relative overflow-hidden bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900">
        <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-6 py-20 md:py-28 lg:grid-cols-[1.2fr_1fr] lg:gap-16 lg:py-32">
            {{-- Left pitch --}}
            <div class="flex flex-col justify-center">
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-medium text-cyan-300">
                    <span class="relative inline-flex h-2 w-2" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-500"></span>
                    </span>
                    Built for SDR teams, agencies &amp; BPOs
                </span>

                <h1 class="mt-6 text-5xl font-semibold tracking-tight text-zinc-100 sm:text-6xl lg:text-7xl">
                    Your team. Your companies.<br>
                    <span class="bg-gradient-to-r from-blue-500 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
                        Your dialer.
                    </span>
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-zinc-400 sm:text-xl">
                    Build an inbound or outbound SDR team on a multi-line power dialer with AI call intelligence baked in. Run one company or twenty. Add agents without per-seat fees. No CRM lock-in, no single-line throttle.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <flux:button href="/signup" variant="primary" icon-trailing="arrow-right">
                        {{ __('Start free trial') }}
                    </flux:button>
                    <flux:button href="/contact-sales" variant="ghost">
                        {{ __('Talk to sales') }}
                    </flux:button>
                </div>

                <p class="mt-6 text-sm text-zinc-500">
                    3-day free trial &middot; No card required to demo &middot; TCPA-compliant out of the box
                </p>
            </div>

            {{-- Right: animated 4-line dialer mockup --}}
            <div class="flex items-center justify-center" aria-hidden="true">
                <div class="w-full max-w-sm rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5 shadow-2xl backdrop-blur">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs font-medium text-zinc-300">
                            <span class="inline-flex h-2 w-2 rounded-full bg-cyan-400 qsdr-dot"></span>
                            Session active &middot; 4 lines
                        </div>
                        <span class="rounded-md bg-zinc-800 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-zinc-400">Live</span>
                    </div>

                    <div class="space-y-2.5">
                        {{-- Line 1: connected --}}
                        <div class="qsdr-line rounded-lg border px-3 py-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono text-zinc-300">+1 (415) 555-0148</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing&hellip;</span>
                                    <span class="qsdr-label-connected text-emerald-300">Connected</span>
                                </span>
                            </div>
                            <div class="mt-1 text-[11px] text-zinc-500">Acme Corp &middot; VP Sales</div>
                        </div>

                        {{-- Line 2: voicemail --}}
                        <div class="qsdr-line-vm qsdr-line-delay-1 rounded-lg border px-3 py-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono text-zinc-300">+1 (312) 555-0102</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing&hellip;</span>
                                    <span class="qsdr-label-voicemail text-zinc-400">Voicemail</span>
                                </span>
                            </div>
                            <div class="mt-1 text-[11px] text-zinc-500">Northbound Co &middot; Director</div>
                        </div>

                        {{-- Line 3: ringing → connected --}}
                        <div class="qsdr-line qsdr-line-delay-2 rounded-lg border px-3 py-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono text-zinc-300">+1 (206) 555-0176</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing&hellip;</span>
                                    <span class="qsdr-label-connected text-emerald-300">Connected</span>
                                </span>
                            </div>
                            <div class="mt-1 text-[11px] text-zinc-500">Meridian Logistics &middot; Ops Lead</div>
                        </div>

                        {{-- Line 4: voicemail --}}
                        <div class="qsdr-line-vm qsdr-line-delay-3 rounded-lg border px-3 py-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono text-zinc-300">+1 (617) 555-0193</span>
                                <span class="relative font-medium">
                                    <span class="qsdr-label-idle absolute inset-0 text-zinc-500">Idle</span>
                                    <span class="qsdr-label-ringing absolute inset-0 text-cyan-300">Ringing&hellip;</span>
                                    <span class="qsdr-label-voicemail text-zinc-400">Voicemail</span>
                                </span>
                            </div>
                            <div class="mt-1 text-[11px] text-zinc-500">Helix Data &middot; Head of RevOps</div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-zinc-800 pt-3 text-[11px] text-zinc-500">
                        <span>First pickup auto-routes</span>
                        <span class="font-mono text-cyan-400">3.2s avg</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        3. Trust bar (stats, not logos — we have none yet)
        ────────────────────────────────────────────────────────── --}}
    <section class="border-y border-zinc-900 bg-zinc-900/40" aria-label="Key statistics">
        <div class="mx-auto max-w-6xl px-6 py-10">
            <dl class="grid grid-cols-2 gap-6 md:grid-cols-5">
                @foreach ([
                    ['5 lines', 'dialed in parallel'],
                    ['Unlimited', 'agents per account'],
                    ['Unlimited', 'companies'],
                    ['$0', 'per-seat fee'],
                    ['TCPA', '+ A2P compliant'],
                ] as [$stat, $label])
                    <div class="text-center">
                        <dt class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-2xl font-semibold tracking-tight text-transparent sm:text-3xl">
                            {{ $stat }}
                        </dt>
                        <dd class="mt-1 text-xs uppercase tracking-wider text-zinc-500">{{ $label }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        4. The Problem (pain narrative)
        ────────────────────────────────────────────────────────── --}}
    <section class="bg-zinc-900 py-24">
        <div class="mx-auto max-w-4xl px-6">
            <div class="mb-12 flex items-center gap-3">
                <flux:icon name="exclamation-triangle" class="h-7 w-7 text-cyan-400" aria-hidden="true" />
                <flux:heading size="xl" class="text-zinc-100">Cold-call dialing is broken.</flux:heading>
            </div>

            <div class="grid gap-8 md:grid-cols-2">
                <div class="border-l-2 border-cyan-500/40 pl-5">
                    <p class="text-zinc-300">
                        <span class="font-semibold text-zinc-100">Single-line dialers throttle your team.</span>
                        One rep, one live connect at a time. Pipeline throughput caps out fast — and there's no room to grow without hiring more bodies.
                    </p>
                </div>

                <div class="border-l-2 border-cyan-500/40 pl-5">
                    <p class="text-zinc-300">
                        <span class="font-semibold text-zinc-100">Your stack punishes growth.</span>
                        CRM per seat. Dialer per seat. Call intelligence per seat. Hire one rep, three vendors raise your bill. Onboard a new client company? Pay again.
                    </p>
                </div>

                <div class="border-l-2 border-cyan-500/40 pl-5">
                    <p class="text-zinc-300">
                        <span class="font-semibold text-zinc-100">Your CRM owns your data.</span>
                        Want to call a list that lives outside your CRM? Can't do it. Want to export transcripts? Enterprise plan upgrade. Want to leave? Good luck migrating two years of activity history.
                    </p>
                </div>

                <div class="border-l-2 border-cyan-500/40 pl-5">
                    <p class="text-zinc-300">
                        <span class="font-semibold text-zinc-100">Auto-dialers without intelligence burn numbers.</span>
                        Spam-flagged caller IDs, TCPA violations, and blocked carriers tank your deliverability overnight — and the tools that are supposed to prevent this charge extra for compliance.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        5. The Solution — 4 feature pillars (2x2 grid)
        ────────────────────────────────────────────────────────── --}}
    <section id="features" class="bg-gradient-to-b from-zinc-950 to-zinc-900 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <flux:heading size="xl" class="text-zinc-100 sm:text-4xl">
                    One platform. Every piece you actually need.
                </flux:heading>
                <flux:text class="mt-4 text-lg text-zinc-400">
                    Built by closers for closers. No feature bloat, no SaaS theatre — just the tools that move pipeline.
                </flux:text>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {{-- Pillar 1 --}}
                <flux:card class="group border-zinc-800 transition hover:border-cyan-500/40">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="phone-arrow-up-right" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">Multi-line Power Dialer</flux:heading>
                    <flux:text class="mt-2 text-zinc-400">
                        Dial up to 5 numbers in parallel. The first human pickup auto-routes to your rep's screen. The other lines drop instantly — no awkward double-connects.
                    </flux:text>
                </flux:card>

                {{-- Pillar 2 --}}
                <flux:card class="group border-zinc-800 transition hover:border-cyan-500/40">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="sparkles" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">AI Call Intelligence</flux:heading>
                    <flux:text class="mt-2 text-zinc-400">
                        Every call transcribed, sentiment-scored, and summarized in real time. A lead score lands on the contact card before the call ends.
                    </flux:text>
                </flux:card>

                {{-- Pillar 3 — NEW: Team + Multi-Company --}}
                <flux:card class="group border-cyan-500/30 bg-cyan-500/5 transition hover:border-cyan-500/60">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="user-group" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">Unlimited Team &amp; Companies</flux:heading>
                    <flux:text class="mt-2 text-zinc-400">
                        Add agents, managers, and entire client companies without per-seat fees. Run inbound, outbound, or both — one flat monthly cost, scale whenever you're ready.
                    </flux:text>
                </flux:card>

                {{-- Pillar 4 --}}
                <flux:card class="group border-zinc-800 transition hover:border-cyan-500/40">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="queue-list" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">Smart Queue &amp; Sequences</flux:heading>
                    <flux:text class="mt-2 text-zinc-400">
                        Cadence auto-enrollment, callback rules, per-timezone TCPA windows, and missed-call recovery — all built in, all configurable per campaign.
                    </flux:text>
                </flux:card>

                {{-- Pillar 5 — NEW: CRM-agnostic --}}
                <flux:card class="group border-zinc-800 transition hover:border-cyan-500/40">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="link" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">No CRM Lock-in</flux:heading>
                    <flux:text class="mt-2 text-zinc-400">
                        Use QuickSDR as your source of truth, or push contacts, dispositions, and transcripts into Salesforce, HubSpot, or your own CRM via webhook. Your data flows where you need it.
                    </flux:text>
                </flux:card>

                {{-- Pillar 6 --}}
                <flux:card class="group border-zinc-800 transition hover:border-cyan-500/40">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="shield-check" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">Compliance &amp; Deliverability</flux:heading>
                    <flux:text class="mt-2 text-zinc-400">
                        TCPA test mode, A2P-aware messaging, AMD whitelisting, and automatic carrier-flag mitigation so your numbers don't get blocked.
                    </flux:text>
                </flux:card>
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        6. Deep-dive sections (3, alternating)
        ────────────────────────────────────────────────────────── --}}

    {{-- 6a. Power Dialer (image right) --}}
    <section id="deepdive-dialer" class="bg-zinc-950 py-24">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-400">Power Dialer</span>
                <flux:heading size="xl" class="mt-3 text-zinc-100">
                    Five lines, one rep, zero dead air.
                </flux:heading>
                <flux:text class="mt-4 text-lg text-zinc-400">
                    Our parallel dialer starts all five calls simultaneously. The first human answer gets routed instantly. Machines, voicemails, and non-connects are dropped before your rep even hears them.
                </flux:text>
                <ul class="mt-6 space-y-3">
                    @foreach ([
                        'Zero-latency handoff — muted-join architecture means the prospect hears you the moment you pick up.',
                        'Automatic throttle that backs off before TCPA abandonment rate triggers.',
                        'Voicemail drops in one click — leave a pre-recorded message without ringing the phone.',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <flux:icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-cyan-400" aria-hidden="true" />
                            <span class="text-zinc-300">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="flex aspect-[4/3] items-center justify-center rounded-2xl border border-zinc-800 bg-gradient-to-br from-blue-900/40 to-cyan-900/40 p-12 shadow-2xl">
                <flux:icon name="phone" class="h-32 w-32 text-cyan-300/80" aria-hidden="true" />
            </div>
        </div>
    </section>

    {{-- 6b. AI Intelligence (image left) --}}
    <section id="deepdive-ai" class="bg-zinc-900 py-24">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <div class="order-2 flex aspect-[4/3] items-center justify-center rounded-2xl border border-zinc-800 bg-gradient-to-br from-blue-900/40 to-cyan-900/40 p-12 shadow-2xl lg:order-1">
                <flux:icon name="chart-bar-square" class="h-32 w-32 text-cyan-300/80" aria-hidden="true" />
            </div>
            <div class="order-1 lg:order-2">
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-400">AI Call Intelligence</span>
                <flux:heading size="xl" class="mt-3 text-zinc-100">
                    Know which calls mattered — before you hang up.
                </flux:heading>
                <flux:text class="mt-4 text-lg text-zinc-400">
                    Real-time transcription streams into a scoring model tuned for B2B outbound. You get disposition suggestions, objection flags, and next-step prompts while the conversation is still live.
                </flux:text>
                <ul class="mt-6 space-y-3">
                    @foreach ([
                        'Plug in OpenAI, Anthropic, Google, or your own LLM — your keys, your data.',
                        'Automatic call summaries drop into your CRM timeline. No post-call note-taking.',
                        'Lead scoring on every conversation so your reps know who to call back first.',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <flux:icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-cyan-400" aria-hidden="true" />
                            <span class="text-zinc-300">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- 6c. Smart Queue (image right) --}}
    <section id="deepdive-queue" class="bg-zinc-950 py-24">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-400">Smart Queue</span>
                <flux:heading size="xl" class="mt-3 text-zinc-100">
                    The next best call, always teed up.
                </flux:heading>
                <flux:text class="mt-4 text-lg text-zinc-400">
                    Smart Queue watches every campaign, callback, and cadence step — and feeds your reps the call most likely to move pipeline right now. No spreadsheets, no "who do I call next?" hesitation.
                </flux:text>
                <ul class="mt-6 space-y-3">
                    @foreach ([
                        'Per-timezone calling windows keep you inside TCPA hours automatically.',
                        'Missed-call recovery — inbound hits from previously-dialed leads jump the queue.',
                        'Multi-agent concurrency keeps two reps from ever calling the same contact at the same time.',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <flux:icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-cyan-400" aria-hidden="true" />
                            <span class="text-zinc-300">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="flex aspect-[4/3] items-center justify-center rounded-2xl border border-zinc-800 bg-gradient-to-br from-blue-900/40 to-cyan-900/40 p-12 shadow-2xl">
                <flux:icon name="sparkles" class="h-32 w-32 text-cyan-300/80" aria-hidden="true" />
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        7. How it works
        ────────────────────────────────────────────────────────── --}}
    <section class="bg-zinc-900 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <flux:heading size="xl" class="text-zinc-100 sm:text-4xl">Stand up your dialing team.</flux:heading>
                <flux:text class="mt-4 text-lg text-zinc-400">
                    Self-serve if you're already on Twilio, or we do the heavy lifting for you. No procurement cycle, no per-seat contract, no lock-in.
                </flux:text>
            </div>

            <div class="mt-16 grid gap-10 md:grid-cols-3">
                @foreach ([
                    ['1', 'Pick your tier', 'Self-serve if you already run Twilio + AI. Or pick Tier 2/3 and we handle Twilio A2P, AI keys, and call-flow setup for you end-to-end.'],
                    ['2', 'Add your agents & companies', 'Invite as many reps and managers as you need. Onboard one company or twenty — no per-seat fees, no per-company surcharges.'],
                    ['3', 'Start dialing', 'Import contacts, set a cadence, hit Dial. QuickSDR handles routing, compliance, scoring, and pipes the data wherever your CRM needs it.'],
                ] as [$n, $title, $desc])
                    <div class="text-center md:text-left">
                        <div class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 via-cyan-500 to-blue-500 text-xl font-bold text-white shadow-lg shadow-cyan-500/20 md:mx-0">
                            {{ $n }}
                        </div>
                        <flux:heading size="lg" class="mt-5 text-zinc-200">{{ $title }}</flux:heading>
                        <flux:text class="mt-2 text-zinc-400">{{ $desc }}</flux:text>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        8. Comparison table
        ────────────────────────────────────────────────────────── --}}
    <section class="bg-zinc-950 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <flux:heading size="xl" class="text-zinc-100 sm:text-4xl">How we stack up.</flux:heading>
                <flux:text class="mt-4 text-lg text-zinc-400">
                    An honest comparison. If you're doing fine with manual dialing, stay — we'd rather lose the deal than oversell it.
                </flux:text>
            </div>

            {{-- Desktop/tablet: table --}}
            <div class="mt-12 hidden overflow-hidden rounded-2xl border border-zinc-800 md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-900 text-xs uppercase tracking-wider text-zinc-400">
                        <tr>
                            <th class="px-6 py-4 font-medium">Feature</th>
                            <th class="px-6 py-4 font-medium">Manual dialing</th>
                            <th class="px-6 py-4 font-medium">Generic single-line dialer</th>
                            <th class="bg-cyan-500/5 px-6 py-4 font-medium text-cyan-300">QuickSDR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800 bg-zinc-900/40">
                        @php
                            $rows = [
                                ['Dials per rep per hour',       '30–40',                                            '80–120',                                            '300+'],
                                ['Per-agent fee',                'N/A',                                              '$80–$150 / user / mo',                              ['badge', 'yes', 'None — flat price']],
                                ['Multi-company support',        ['badge', 'missing', '—'],                          ['badge', 'missing', 'Per-brand fees'],              ['badge', 'yes', 'Unlimited']],
                                ['AI call scoring',              ['badge', 'missing', '—'],                          ['badge', 'missing', '—'],                           ['badge', 'yes', 'Built in']],
                                ['TCPA-aware calling hours',     ['badge', 'missing', 'Manual'],                     ['badge', 'partial', 'Basic'],                       ['badge', 'yes', 'Per-timezone']],
                                ['Dedicated server',             ['badge', 'missing', '—'],                          ['badge', 'missing', 'Shared tenant'],               ['badge', 'yes', 'Single-tenant']],
                                ['CRM lock-in',                  '—',                                                ['badge', 'partial', 'Your CRM owns data'],          ['badge', 'yes', 'Works with any / standalone']],
                                ['Voicemail drops',              ['badge', 'missing', '—'],                          ['badge', 'partial', 'Add-on'],                      ['badge', 'yes', 'Included']],
                            ];
                        @endphp
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-200">{{ $row[0] }}</td>
                                @foreach ([$row[1], $row[2], $row[3]] as $i => $cell)
                                    <td class="px-6 py-4 {{ $i === 2 ? 'bg-cyan-500/5' : '' }}">
                                        @if (is_array($cell) && $cell[0] === 'badge')
                                            @if ($cell[1] === 'yes')
                                                <flux:badge variant="success" size="sm" icon="check">{{ $cell[2] }}</flux:badge>
                                            @elseif ($cell[1] === 'partial')
                                                <flux:badge color="zinc" size="sm">{{ $cell[2] }}</flux:badge>
                                            @else
                                                <span class="text-zinc-500">{{ $cell[2] }}</span>
                                            @endif
                                        @else
                                            <span class="{{ $i === 2 ? 'font-semibold text-cyan-200' : 'text-zinc-300' }}">{{ $cell }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: stacked cards --}}
            <div class="mt-10 space-y-4 md:hidden">
                @foreach ($rows as $row)
                    <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
                        <div class="text-sm font-semibold text-zinc-200">{{ $row[0] }}</div>
                        <dl class="mt-3 grid grid-cols-1 gap-2 text-sm">
                            @foreach (['Manual' => $row[1], 'Single-line' => $row[2], 'QuickSDR' => $row[3]] as $label => $cell)
                                <div class="flex items-start justify-between gap-3 {{ $label === 'QuickSDR' ? 'rounded-md bg-cyan-500/5 px-2 py-1' : '' }}">
                                    <dt class="text-xs uppercase tracking-wider text-zinc-500">{{ $label }}</dt>
                                    <dd class="text-right">
                                        @if (is_array($cell) && $cell[0] === 'badge')
                                            @if ($cell[1] === 'yes')
                                                <flux:badge variant="success" size="sm" icon="check">{{ $cell[2] }}</flux:badge>
                                            @elseif ($cell[1] === 'partial')
                                                <flux:badge color="zinc" size="sm">{{ $cell[2] }}</flux:badge>
                                            @else
                                                <span class="text-zinc-500">{{ $cell[2] }}</span>
                                            @endif
                                        @else
                                            <span class="{{ $label === 'QuickSDR' ? 'font-semibold text-cyan-200' : 'text-zinc-300' }}">{{ $cell }}</span>
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
        9. Pricing
        ────────────────────────────────────────────────────────── --}}
    <section id="pricing" class="bg-gradient-to-b from-zinc-950 to-zinc-900 py-24">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <flux:heading size="xl" class="text-zinc-100 sm:text-4xl">Pricing that scales with your team.</flux:heading>
                <flux:text class="mt-4 text-lg text-zinc-400">
                    Every tier ships on a dedicated, single-tenant server. Your data, your server, your control.
                </flux:text>
            </div>

            <div class="mt-16 grid gap-6 lg:grid-cols-4">
                {{-- Tier 1: Self-serve --}}
                <div class="flex flex-col rounded-2xl border border-zinc-800 bg-zinc-900/50 p-8">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-100">Self-serve</h3>
                        <p class="mt-1 text-sm text-zinc-400">For a solo closer or tight team ready to plug in their own keys.</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-bold text-zinc-100">$350</span>
                        <span class="text-zinc-400">/ month</span>
                    </div>
                    <ul class="mt-6 flex-1 space-y-3 text-sm">
                        @foreach ([
                            'Dedicated single-tenant server',
                            'Connect your own Twilio + AI keys',
                            '5-line parallel dialer',
                            'AI transcription & scoring',
                            'Email support',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-300">
                                <flux:icon name="check" class="mt-0.5 h-4 w-4 flex-none text-cyan-400" aria-hidden="true" />
                                {{ $bullet }}
                            </li>
                        @endforeach
                    </ul>
                    <flux:button href="/signup?tier=1" variant="primary" class="mt-8 w-full" icon-trailing="arrow-right">
                        Start free trial
                    </flux:button>
                </div>

                {{-- Tier 2: Done-for-you (most popular) --}}
                <div class="relative flex flex-col rounded-2xl border border-cyan-500/60 bg-zinc-900/60 p-8 shadow-xl shadow-cyan-500/10 lg:scale-105">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white shadow">
                            Most Popular
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-100">Done-for-you</h3>
                        <p class="mt-1 text-sm text-zinc-400">We register Twilio, wire your AI, and design the call flows for you.</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-bold text-zinc-100">$1,000</span>
                        <span class="text-zinc-400"> setup</span>
                        <div class="mt-1 text-sm text-zinc-400">+ $350/mo &middot; <span class="text-cyan-300">first 2 months waived</span></div>
                    </div>
                    <ul class="mt-6 flex-1 space-y-3 text-sm">
                        @foreach ([
                            'Everything in Self-serve',
                            'We do your Twilio A2P registration',
                            'We configure your AI providers',
                            'We design your call flows + cadences',
                            'Priority support + Slack channel',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-300">
                                <flux:icon name="check" class="mt-0.5 h-4 w-4 flex-none text-cyan-400" aria-hidden="true" />
                                {{ $bullet }}
                            </li>
                        @endforeach
                    </ul>
                    <flux:button href="/signup?tier=2" variant="primary" class="mt-8 w-full" icon-trailing="arrow-right">
                        Book your setup call
                    </flux:button>
                    <p class="mt-3 text-center text-xs italic text-zinc-500">
                        We'll send your details to Matthew's dialer so he can follow up directly.
                    </p>
                </div>

                {{-- Tier 3: Multi-location + training --}}
                <div class="flex flex-col rounded-2xl border border-zinc-800 bg-zinc-900/50 p-8">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-100">Multi-location</h3>
                        <p class="mt-1 text-sm text-zinc-400">Multiple offices, live training, and hands-on rollout across your org.</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-bold text-zinc-100">$3,000</span>
                        <span class="text-zinc-400"> setup</span>
                        <div class="mt-1 text-sm text-zinc-400">+ $350/mo &middot; <span class="text-cyan-300">first 2 months waived</span></div>
                    </div>
                    <ul class="mt-6 flex-1 space-y-3 text-sm">
                        @foreach ([
                            'Everything in Done-for-you',
                            'Multi-location configuration',
                            'Live SDR training sessions',
                            'Live admin / manager training',
                            'Upgraded server tier for higher volume',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-300">
                                <flux:icon name="check" class="mt-0.5 h-4 w-4 flex-none text-cyan-400" aria-hidden="true" />
                                {{ $bullet }}
                            </li>
                        @endforeach
                    </ul>
                    <flux:button href="/signup?tier=3" variant="primary" class="mt-8 w-full" icon-trailing="arrow-right">
                        Book a strategy call
                    </flux:button>
                    <p class="mt-3 text-center text-xs italic text-zinc-500">
                        We'll send your details to Matthew's dialer so he can follow up directly.
                    </p>
                </div>

                {{-- Tier 4: Custom --}}
                <div class="flex flex-col rounded-2xl border border-zinc-800 bg-zinc-900/50 p-8">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-100">Custom build</h3>
                        <p class="mt-1 text-sm text-zinc-400">Custom AI agents, deep integrations, and engineering on demand.</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-bold text-zinc-100">$160</span>
                        <span class="text-zinc-400">/ hour</span>
                    </div>
                    <ul class="mt-6 flex-1 space-y-3 text-sm">
                        @foreach ([
                            'Custom AI call agents',
                            'Custom CRM & tooling integrations',
                            'Hourly engineering against your backlog',
                            'Architecture + security review',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-300">
                                <flux:icon name="check" class="mt-0.5 h-4 w-4 flex-none text-cyan-400" aria-hidden="true" />
                                {{ $bullet }}
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
        10. Testimonials (placeholder)
        ────────────────────────────────────────────────────────── --}}
    <section class="bg-zinc-950 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <flux:heading size="xl" class="text-zinc-100 sm:text-4xl">Teams are already dialing smarter.</flux:heading>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['JK', 'Jamie K.',   'Head of Sales, FinTech SaaS',      'Our 3-person team dials like a 10-person team. Pipeline is 4× higher in six weeks.'],
                    ['TR', 'Taylor R.',  'SDR Lead, B2B Services',           'The AI scoring means we stop wasting time on tire-kickers. Period.'],
                    ['MH', 'Morgan H.',  'VP Sales, Logistics Co.',          'Setup took a morning. We were live dialing by lunch.'],
                ] as [$initials, $name, $title, $quote])
                    <flux:card class="border-zinc-800">
                        <span class="text-5xl leading-none text-cyan-400" aria-hidden="true">&ldquo;</span>
                        <flux:text class="mt-2 text-zinc-200">{{ $quote }}</flux:text>
                        <div class="mt-6 flex items-center gap-3 border-t border-zinc-800 pt-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-semibold text-white" aria-hidden="true">
                                {{ $initials }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-zinc-100">{{ $name }}</div>
                                <div class="text-xs text-zinc-500">{{ $title }}</div>
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        11. FAQ (accordion)
        ────────────────────────────────────────────────────────── --}}
    <section id="faq" class="bg-zinc-900 py-24">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <flux:heading size="xl" class="text-zinc-100 sm:text-4xl">Frequently asked questions.</flux:heading>
                <flux:text class="mt-4 text-lg text-zinc-400">
                    Can't find what you need? <flux:link href="/contact-sales" class="text-cyan-400">Ask us directly.</flux:link>
                </flux:text>
            </div>

            <div class="mt-12 divide-y divide-zinc-800 rounded-2xl border border-zinc-800 bg-zinc-900/40">
                @foreach ([
                    ['How is this different from Aircall, JustCall, or Outreach?',
                     "Those tools are great single-line dialers on a shared SaaS tenant. QuickSDR is a 5-line parallel dialer on a dedicated server that's yours, with AI call intelligence built in — not bolted on. You own your data, your numbers, and your deliverability reputation."],
                    ['What does multi-line dialing actually mean?',
                     'When your rep starts a session, QuickSDR rings up to 5 numbers simultaneously. The first human who picks up is routed to your rep instantly. The other calls drop before the prospect can even say hello — no double-connects, no awkward apologies.'],
                    ['Will I get flagged as spam?',
                     'Every outbound carrier has spam-flag heuristics. QuickSDR fights them proactively: automatic abandonment-rate throttling, TCPA window enforcement per timezone, AMD whitelisting, and local-presence rotation if you enable it. You stay inside the rails the carriers actually care about.'],
                    ['How long does setup take?',
                     "It depends on what you're starting with. If you already have Twilio + AI keys, Tier 1 is quick — connect and dial. If you're new to Twilio, A2P registration can take 1–2 weeks with carriers, AI providers need configuration, and call flows should match your playbook — that's what Tier 2 and 3 cover: we do that work for you so you don't spend a month learning the stack."],
                    ['Do I pay per agent or per company?',
                     "No on both. QuickSDR is flat per month per server — you can add unlimited agents and unlimited companies without your bill changing. If you're running SDR for multiple brands (agency, BPO, holding company), this is the biggest savings vs. per-seat tools."],
                    ['Do I need my own Twilio account?',
                     'Yes — QuickSDR rides on your Twilio account so your numbers, your A2P registration, and your spending stay on your books. On Tier 2 and up, we handle the registration and configuration for you; on Tier 1, you connect your own keys in the dashboard.'],
                    ['What AI providers can I use?',
                     'OpenAI, Anthropic, Google, and any OpenAI-compatible endpoint out of the box. Bring your own API keys — your transcripts and scoring data go directly to your provider, not through us.'],
                    ['Can I export my data if I cancel?',
                     'Always. Your data stays exportable (contacts, call logs, transcripts, recordings) for 7 days after cancellation. No lock-in, no "contact your account manager" runaround.'],
                    ['Is there a free trial?',
                     'Yes — 3 days free on Tier 1, no card required to demo. You can cancel from the dashboard any time during the trial.'],
                ] as [$question, $answer])
                    <details class="group px-6 py-5 [&[open]]:bg-zinc-900/60">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-zinc-100 outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/60 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900 rounded">
                            <span class="font-medium">{{ $question }}</span>
                            <flux:icon name="chevron-down" class="h-5 w-5 flex-none text-zinc-500 transition group-open:rotate-180 group-open:text-cyan-400" aria-hidden="true" />
                        </summary>
                        <div class="mt-3 text-zinc-400">{{ $answer }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        12. Final CTA (brand gradient)
        ────────────────────────────────────────────────────────── --}}
    <section class="bg-gradient-to-r from-blue-600 via-cyan-500 to-blue-500 py-20">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <h2 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                Build your team without the per-seat tax.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-white/90">
                Unlimited agents, unlimited companies, dedicated server. Scale when you're ready — your bill doesn't change.
            </p>
            <div class="mt-8 flex justify-center">
                <a href="/signup"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-blue-700 shadow-xl transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50">
                    Start free trial
                    <flux:icon name="arrow-right" class="h-5 w-5" aria-hidden="true" />
                </a>
            </div>
            <p class="mt-6 text-sm text-white/80">
                3-day free trial &middot; No card required to demo &middot; Cancel anytime — your data stays exportable for 7 days.
            </p>
        </div>
    </section>

    {{-- ──────────────────────────────────────────────────────────
        13. Footer
        ────────────────────────────────────────────────────────── --}}
    <footer class="border-t border-zinc-800 bg-zinc-900 py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-10 md:grid-cols-3">
                {{-- Brand --}}
                <div>
                    <a href="{{ route('home') }}" class="inline-block" aria-label="QuickSDR home">
                        <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
                    </a>
                    <p class="mt-4 max-w-xs text-sm text-zinc-400">
                        Build an SDR team without the per-seat tax. Multi-line power dialer + AI call intelligence, on a dedicated server that's yours.
                    </p>
                    <p class="mt-6 text-xs text-zinc-600">&copy; {{ date('Y') }} QuickSDR. All rights reserved.</p>
                </div>

                {{-- Product --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-300">Product</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="#features" class="text-zinc-400 transition hover:text-zinc-100">Features</a></li>
                        <li><a href="#pricing" class="text-zinc-400 transition hover:text-zinc-100">Pricing</a></li>
                        <li><a href="#faq" class="text-zinc-400 transition hover:text-zinc-100">FAQ</a></li>
                        <li><a href="/status" class="text-zinc-400 transition hover:text-zinc-100">Status</a></li>
                    </ul>
                </div>

                {{-- Company --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-300">Company</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="/about" class="text-zinc-400 transition hover:text-zinc-100">About</a></li>
                        <li><a href="/contact" class="text-zinc-400 transition hover:text-zinc-100">Contact</a></li>
                        <li><a href="/contact-sales" class="text-zinc-400 transition hover:text-zinc-100">Talk to sales</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
