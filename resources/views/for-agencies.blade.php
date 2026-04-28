<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => __('For Agencies & VAs — Resell phone answering as a service · QuickSDR')])

    <meta name="description" content="Add phone answering as a new revenue layer for your client roster. QuickSDR lets agencies, VAs, and CMS firms offer next-level inbound service without hiring a phone team.">
    <meta property="og:title" content="For Agencies & VAs — Resell phone answering as a service">
    <meta property="og:description" content="Add phone answering as a new revenue layer for your client roster — without hiring a phone team.">
    <meta property="og:image" content="/brand/qsdr-logo-light.png">
    <meta property="og:type" content="website">

    <style>
        html { scroll-behavior: smooth; }

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

        @keyframes qsdr-ivr-walk {
            0%, 18%   { background-color: rgb(24 24 27); border-color: rgb(63 63 70); color: rgb(161 161 170); }
            24%, 70%  { background-color: rgb(8 47 73);  border-color: rgb(14 116 144); color: rgb(165 243 252); }
            76%, 100% { background-color: rgb(24 24 27); border-color: rgb(63 63 70); color: rgb(161 161 170); }
        }
        .qsdr-ivr       { animation: qsdr-ivr-walk 7s ease-in-out infinite; }
        .qsdr-ivr-d-1   { animation-delay: 0.0s; }
        .qsdr-ivr-d-2   { animation-delay: 2.3s; }
        .qsdr-ivr-d-3   { animation-delay: 4.6s; }

        @keyframes qsdr-ring-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.5);  transform: scale(1); }
            50%      { box-shadow: 0 0 0 12px rgba(6, 182, 212, 0); transform: scale(1.05); }
        }
        .qsdr-ring-pulse { animation: qsdr-ring-pulse 2.4s ease-in-out infinite; }

        @keyframes qsdr-route-arrow {
            0%, 100% { opacity: 0.4; transform: translateX(0); }
            50%      { opacity: 1;   transform: translateX(4px); }
        }
        .qsdr-route-arrow { animation: qsdr-route-arrow 2s ease-in-out infinite; }

        @keyframes qsdr-routed-fade {
            0%, 65%   { opacity: 0; transform: translateY(4px); }
            75%, 100% { opacity: 1; transform: translateY(0); }
        }
        .qsdr-routed-fade { animation: qsdr-routed-fade 7s ease-in-out infinite; opacity: 0; }

        @media (prefers-reduced-motion: reduce) {
            .qsdr-ivr, .qsdr-ring-pulse, .qsdr-route-arrow, .qsdr-routed-fade {
                animation: none; opacity: 1;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased">

    <header class="sticky top-0 z-50 border-b border-zinc-200 bg-white/85 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="QuickSDR home">
                <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
            </a>

            <nav class="hidden items-center gap-7 text-sm text-zinc-600 md:flex" aria-label="Primary">
                <a href="{{ route('for-sales-teams') }}" class="transition hover:text-zinc-900">For Sales Teams</a>
                <a href="{{ route('for-agencies') }}" class="font-semibold text-zinc-900">For Agencies</a>
                <a href="{{ route('home') }}#pricing" class="transition hover:text-zinc-900">Pricing</a>
                <a href="{{ route('home') }}#faq" class="transition hover:text-zinc-900">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                <flux:button href="/signup" variant="primary" size="sm" icon-trailing="arrow-right">
                    Start free trial
                </flux:button>
            </div>
        </div>
    </header>

    {{-- 1. Hero (DARK) --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100">
        <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-6 py-20 md:py-24 lg:grid-cols-[1.15fr_1fr] lg:gap-16 lg:py-28">
            <div class="flex flex-col justify-center">
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-cyan-400/40 bg-cyan-500/10 px-3 py-1 text-xs font-medium text-cyan-200">
                    <flux:icon name="building-office-2" class="h-3.5 w-3.5" />
                    For agencies, VAs &amp; CMS firms
                </span>

                <h1 class="mt-6 text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                    Offer next-level service to your clients —<br>
                    <span class="bg-gradient-to-r from-blue-400 via-cyan-300 to-blue-300 bg-clip-text text-transparent">
                        without hiring a phone team.
                    </span>
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-zinc-200">
                    You already do their bookkeeping, scheduling, or admin. Phone answering is the next layer — and now you can deliver it to <strong class="text-cyan-200">every client on your roster</strong> from one platform, without staffing a call center.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <flux:button href="/contact-sales" variant="primary" icon-trailing="arrow-right">
                        Talk to us about your client roster
                    </flux:button>
                    <flux:button href="/signup" variant="ghost">
                        Start free trial
                    </flux:button>
                </div>
            </div>

            <div class="flex items-center justify-center" aria-hidden="true">
                <div class="w-full max-w-sm rounded-2xl border border-zinc-800 bg-zinc-900/70 p-5 shadow-2xl backdrop-blur">
                    <div class="mb-4 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 font-medium text-zinc-200">
                            <span class="qsdr-ring-pulse inline-flex h-7 w-7 items-center justify-center rounded-full bg-cyan-500/20 text-cyan-300">
                                <flux:icon name="phone-arrow-down-left" class="h-3.5 w-3.5" />
                            </span>
                            Inbound · Acme Realty
                        </div>
                        <span class="rounded-md bg-zinc-800 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-zinc-300">Live</span>
                    </div>

                    <p class="mb-3 text-[11px] text-zinc-400">"Thanks for calling Acme Realty. Press…"</p>

                    <div class="space-y-2">
                        <div class="qsdr-ivr qsdr-ivr-d-1 flex items-center justify-between rounded-lg border px-3 py-2.5">
                            <span class="text-xs">
                                <span class="font-mono mr-2 text-cyan-300">1</span>
                                New listings
                            </span>
                            <flux:icon name="arrow-right" class="qsdr-route-arrow h-3.5 w-3.5" />
                        </div>
                        <div class="qsdr-ivr qsdr-ivr-d-2 flex items-center justify-between rounded-lg border px-3 py-2.5">
                            <span class="text-xs">
                                <span class="font-mono mr-2 text-cyan-300">2</span>
                                Existing clients
                            </span>
                            <flux:icon name="arrow-right" class="qsdr-route-arrow h-3.5 w-3.5" />
                        </div>
                        <div class="qsdr-ivr qsdr-ivr-d-3 flex items-center justify-between rounded-lg border px-3 py-2.5">
                            <span class="text-xs">
                                <span class="font-mono mr-2 text-cyan-300">3</span>
                                Owner direct
                            </span>
                            <flux:icon name="arrow-right" class="qsdr-route-arrow h-3.5 w-3.5" />
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-zinc-800 pt-3 text-[11px]">
                        <span class="text-zinc-400">Routed to:</span>
                        <span class="qsdr-routed-fade font-mono text-emerald-300">Agent A · 3.1s</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. The opportunity (LIGHT) --}}
    <section class="bg-white py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                Your clients hate missed calls. You can charge to fix it.
            </h2>
            <div class="mt-6 space-y-5 text-lg leading-relaxed text-zinc-700">
                <p>
                    Every small business loses revenue to the same problem: someone calls during business hours and nobody picks up. They Google the next provider and never call back.
                </p>
                <p>
                    You already sit between your clients and their operations — bookkeeping, scheduling, admin, marketing. <strong class="text-zinc-900">Phone answering is the next layer.</strong> The only reason you haven't sold it is that staffing a phone team is expensive, hard to manage, and doesn't scale across clients.
                </p>
                <p>
                    QuickSDR lets one or two of your existing team members handle inbound calls for your <em>entire roster</em>. Per-client greetings, per-client routing, branded experience for each one. Your client gets next-level service. You get a new revenue line without new payroll.
                </p>
            </div>
        </div>
    </section>

    {{-- 3. Outcomes band (DARK, stats) --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-zinc-950 py-20 text-zinc-100">
        <div class="relative z-10 mx-auto max-w-5xl px-6">
            <div class="text-center">
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-300">What this looks like in practice</span>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                    A new revenue layer that scales across every client.
                </h2>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['$200–$600 / mo', 'per client you bill for answering — pure margin on top of services you already deliver'],
                    ['10–20 clients',  'one agent can comfortably cover with QuickSDR\'s parallel queue + IVR routing'],
                    ['<5 seconds',     'average inbound pickup, even at peak hours — your client\'s customers never hit voicemail'],
                ] as [$stat, $label])
                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 text-center backdrop-blur">
                        <div class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-3xl font-bold tracking-tight text-transparent">
                            {{ $stat }}
                        </div>
                        <p class="mt-2 text-sm text-zinc-300">{{ $label }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mx-auto mt-8 max-w-2xl text-center text-sm text-zinc-400">
                Numbers depend on your market and the service level your clients want — most agencies start with 3–5 pilot clients and expand from there.
            </p>
        </div>
    </section>

    {{-- 4. How it works for an agency (LIGHT) --}}
    <section class="bg-zinc-50 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                    Three steps to a new service line.
                </h2>
                <p class="mt-4 text-lg text-zinc-600">
                    From "we should sell phone answering" to billing your first client — usually inside two weeks.
                </p>
            </div>

            <div class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['1', 'Onboard your roster',
                     'Each client gets their own number, greeting, and IVR menu inside one QuickSDR account. Per-client branding, per-client routing — your agents see who they\'re answering for the second the call arrives.'],
                    ['2', 'Route to your team',
                     'Your existing team members handle calls in parallel. The smart queue distributes inbound across whoever\'s available, with TCPA hours and after-hours fallbacks per client.'],
                    ['3', 'Bill the new layer',
                     'Charge each client a flat monthly fee for phone coverage on top of what you already invoice. No per-seat costs on your end — your QuickSDR bill stays flat as you add clients.'],
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

    {{-- 5. Feature highlights (LIGHT, 4 tiles) --}}
    <section class="bg-white py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                    Every feature your clients will notice.
                </h2>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['squares-2x2',         'Per-client routing',     'Each client gets their own number, greeting, IVR, and queue. Your team sees the client name on every call.'],
                    ['speaker-wave',        'Branded greetings',      'Custom audio per client. Their callers hear "Thanks for calling [Client Name]" — never your agency.'],
                    ['document-text',       'Transcripts to email',   'Every call transcribed and emailed to your client (or their CRM) in real time. Optional summary + sentiment.'],
                    ['users',               'Live agent availability', 'Your team toggles available/away. Calls only route to agents who are actually online — no missed connects.'],
                ] as [$icon, $title, $desc])
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 transition hover:-translate-y-0.5 hover:border-cyan-400/60 hover:shadow-md">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-md shadow-cyan-500/20">
                            <flux:icon name="{{ $icon }}" class="h-5 w-5" />
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-zinc-900">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm text-zinc-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 6. Tier callout (LIGHT) --}}
    <section class="bg-zinc-50 py-20">
        <div class="mx-auto max-w-4xl px-6">
            <div class="rounded-3xl border-2 border-cyan-500 bg-white p-8 shadow-xl shadow-cyan-500/15 sm:p-12">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white">
                        Recommended for agencies
                    </span>
                </div>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                    Tier 2 — Setup + Use-Case Consultation
                </h2>
                <p class="mt-4 text-lg text-zinc-700">
                    Most agencies pick Tier 2. We sit down with you, look at your client roster, and design the per-client routing + pricing model together. You're not figuring out IVR trees from a docs page — you're launching with a structure built for <em>your</em> business.
                </p>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        '90-min strategy call to map your client roster and pricing model',
                        'Twilio + AI configured for multi-tenant routing',
                        'IVR + greetings designed for each pilot client',
                        'Slack channel for ongoing rollout questions',
                    ] as $bullet)
                        <div class="flex items-start gap-2 text-sm text-zinc-700">
                            <flux:icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-cyan-600" />
                            <span>{{ $bullet }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-col items-start gap-4 border-t border-zinc-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-zinc-900">$1,000</span>
                            <span class="text-zinc-600">setup</span>
                        </div>
                        <div class="mt-1 text-sm text-zinc-600">
                            + $350/mo &middot; <span class="font-semibold text-cyan-700">first 2 months waived</span>
                        </div>
                    </div>
                    <flux:button href="/signup?tier=2" variant="primary" icon-trailing="arrow-right">
                        Book your strategy call
                    </flux:button>
                </div>
            </div>

            <p class="mt-6 text-center text-sm text-zinc-500">
                Running multiple offices or a really wide roster? <a href="{{ route('home') }}#pricing" class="font-semibold text-cyan-700 hover:text-cyan-600">See Tier 3</a> for multi-location implementation + 90-day strategy partnership.
            </p>
        </div>
    </section>

    {{-- 7. Final CTA (DARK) --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-gradient-to-r from-blue-700 via-blue-600 to-cyan-500 py-20">
        <div class="relative z-10 mx-auto max-w-4xl px-6 text-center">
            <h2 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                Tell us about your client roster.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-white/95">
                We'll help you scope the rollout, price your service, and stand up the first three pilot clients — usually inside two weeks.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="/contact-sales"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-blue-700 shadow-xl transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50">
                    Talk to sales
                    <flux:icon name="arrow-right" class="h-5 w-5" />
                </a>
                <a href="/signup"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-8 py-4 text-base font-semibold text-white backdrop-blur transition hover:bg-white/20 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50">
                    Start free trial
                </a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    @include('partials.public-footer')

    @fluxScripts
</body>
</html>
