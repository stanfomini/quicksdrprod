<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    @include('partials.head', ['title' => __('About — QuickSDR')])
    <meta name="description" content="QuickSDR was built by an SDR team lead who was tired of CRM lock-in, per-seat pricing, and single-line dialer throttles.">
    <meta property="og:title" content="About QuickSDR">
    <meta property="og:description" content="Built by closers who were tired of the tooling tax.">
    <meta property="og:image" content="/brand/qsdr-logo-light.png">
    <meta property="og:type" content="website">
    <style>html { scroll-behavior: smooth; }</style>
</head>
<body class="min-h-screen bg-zinc-950 font-sans text-zinc-100 antialiased">

    {{-- Sticky header --}}
    <header class="sticky top-0 z-50 border-b border-zinc-900/80 bg-zinc-950/80 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center" aria-label="QuickSDR home">
                <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
            </a>
            <nav class="hidden items-center gap-8 text-sm font-medium text-zinc-400 md:flex">
                <a href="{{ route('home') }}#features" class="hover:text-zinc-100 transition">Features</a>
                <a href="{{ route('home') }}#pricing" class="hover:text-zinc-100 transition">Pricing</a>
                <a href="{{ route('home') }}#faq" class="hover:text-zinc-100 transition">FAQ</a>
                <a href="/about" class="text-zinc-100">About</a>
                <a href="/contact" class="hover:text-zinc-100 transition">Contact</a>
            </nav>
            <div class="flex items-center gap-3">
                <flux:button :href="route('login')" variant="ghost" size="sm">{{ __('Sign in') }}</flux:button>
                <flux:button href="/signup" variant="primary" size="sm" icon-trailing="arrow-right">{{ __('Start free trial') }}</flux:button>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900">
        <div class="mx-auto max-w-4xl px-6 py-20 md:py-28 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-medium text-cyan-300">
                About QuickSDR
            </span>
            <h1 class="mt-6 text-5xl font-semibold tracking-tight text-zinc-100 sm:text-6xl">
                Built by closers,<br>
                <span class="bg-gradient-to-r from-blue-500 via-cyan-400 to-blue-400 bg-clip-text text-transparent">
                    for closers.
                </span>
            </h1>
            <p class="mt-6 mx-auto max-w-2xl text-lg leading-relaxed text-zinc-400 sm:text-xl">
                We built QuickSDR because nothing on the market actually worked the way an SDR team needs it to work.
            </p>
        </div>
    </section>

    {{-- Body --}}
    <section class="bg-zinc-900 py-24">
        <div class="mx-auto max-w-3xl px-6 space-y-16">

            <div class="space-y-5">
                <flux:heading size="xl" class="text-zinc-100">The problem we got tired of.</flux:heading>
                <div class="space-y-4 text-lg leading-relaxed text-zinc-400">
                    <p>
                        We've run outbound teams. We've run inbound teams. We've onboarded SDRs at single-product startups,
                        multi-brand agencies, and BPOs that dial for a dozen clients simultaneously.
                    </p>
                    <p>
                        Every stack we tried had the same three problems. <span class="text-zinc-200">One:</span>
                        per-seat fees on the CRM, the dialer, AND the call intelligence tool — so adding one rep raised
                        the bill three separate places. <span class="text-zinc-200">Two:</span> single-line dialers that
                        throttled output just when momentum mattered. <span class="text-zinc-200">Three:</span> data
                        locked inside someone else's CRM, which meant migrating was a full project any time we outgrew
                        a vendor.
                    </p>
                </div>
            </div>

            <div class="space-y-5">
                <flux:heading size="xl" class="text-zinc-100">What we built instead.</flux:heading>
                <div class="space-y-4 text-lg leading-relaxed text-zinc-400">
                    <p>
                        QuickSDR is a <span class="text-zinc-200">multi-line power dialer</span> + AI call intelligence
                        layer, delivered as a dedicated single-tenant Laravel app per customer. Your data lives on your
                        server. Your Twilio account and your numbers stay in your name. Your AI provider keys and
                        transcripts never leave your box.
                    </p>
                    <p>
                        And the pricing is flat. One server, one monthly cost, unlimited agents, unlimited companies
                        — scale your team or take on new client brands without a conversation with an account manager.
                    </p>
                </div>
            </div>

            <div class="space-y-5">
                <flux:heading size="xl" class="text-zinc-100">What we believe.</flux:heading>
                <ul class="space-y-4 text-lg leading-relaxed text-zinc-400">
                    <li class="flex gap-3">
                        <flux:icon name="check-circle" class="mt-1 size-6 shrink-0 text-cyan-400" />
                        <span><span class="text-zinc-200">Your data is yours.</span> You can export everything, any time, in open formats. No "contact your account manager" dance.</span>
                    </li>
                    <li class="flex gap-3">
                        <flux:icon name="check-circle" class="mt-1 size-6 shrink-0 text-cyan-400" />
                        <span><span class="text-zinc-200">Scale should be free.</span> Per-seat pricing punishes growth. You pay for the infrastructure; you shouldn't pay again because you hired a rep.</span>
                    </li>
                    <li class="flex gap-3">
                        <flux:icon name="check-circle" class="mt-1 size-6 shrink-0 text-cyan-400" />
                        <span><span class="text-zinc-200">Compliance is a feature, not a plan upgrade.</span> TCPA hours, A2P-aware SMS, AMD detection, local-presence rotation — all standard.</span>
                    </li>
                    <li class="flex gap-3">
                        <flux:icon name="check-circle" class="mt-1 size-6 shrink-0 text-cyan-400" />
                        <span><span class="text-zinc-200">AI should work for the rep, not replace them.</span> Every call transcribed and scored — so reps close better on the next one, not so AI replaces the conversation.</span>
                    </li>
                    <li class="flex gap-3">
                        <flux:icon name="check-circle" class="mt-1 size-6 shrink-0 text-cyan-400" />
                        <span><span class="text-zinc-200">CRM-agnostic.</span> Use QuickSDR as your source of truth, or pipe it into the CRM you already live in. Whichever fits.</span>
                    </li>
                </ul>
            </div>

            <div class="space-y-5">
                <flux:heading size="xl" class="text-zinc-100">Who's behind it.</flux:heading>
                <div class="space-y-4 text-lg leading-relaxed text-zinc-400">
                    <p>
                        QuickSDR is built and run by <span class="text-zinc-200">Matthew Stanford</span>. Matthew ran
                        outbound SDR teams for years, then started building the tooling he wished he'd had. Today he
                        handles product, engineering, and does the kickoff calls on every Tier 2 and Tier 3 signup
                        personally — because if a new customer needs help registering Twilio A2P or designing their
                        first call flow, the person building the product should be the one solving it.
                    </p>
                    <p>
                        You can reach him directly at
                        <a href="mailto:matthew@scriptandautomations.com" class="text-cyan-400 hover:text-cyan-300 underline underline-offset-4">matthew@scriptandautomations.com</a>
                        or <a href="tel:+17573433032" class="text-cyan-400 hover:text-cyan-300 underline underline-offset-4">(757) 343-3032</a>.
                    </p>
                </div>
            </div>

        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-gradient-to-r from-blue-600 via-cyan-500 to-blue-500 py-20">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <h2 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                Ready to see it?
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-white/90">
                Start a 3-day trial or book a 30-minute walkthrough — your call.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="/signup" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-blue-700 shadow-xl transition hover:bg-zinc-100">
                    Start free trial
                    <flux:icon name="arrow-right" class="h-5 w-5" />
                </a>
                <a href="/contact-sales" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-8 py-4 text-base font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/20">
                    Book a walkthrough
                </a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-zinc-800 bg-zinc-900 py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-10 md:grid-cols-3">
                <div>
                    <a href="{{ route('home') }}" class="inline-block" aria-label="QuickSDR home">
                        <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
                    </a>
                    <p class="mt-4 max-w-xs text-sm text-zinc-400">
                        Build an SDR team without the per-seat tax. Multi-line power dialer + AI call intelligence, on a dedicated server that's yours.
                    </p>
                    <p class="mt-6 text-xs text-zinc-600">&copy; {{ date('Y') }} QuickSDR. All rights reserved.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-300">Product</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('home') }}#features" class="text-zinc-400 transition hover:text-zinc-100">Features</a></li>
                        <li><a href="{{ route('home') }}#pricing" class="text-zinc-400 transition hover:text-zinc-100">Pricing</a></li>
                        <li><a href="{{ route('home') }}#faq" class="text-zinc-400 transition hover:text-zinc-100">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-300">Company</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="/about" class="text-zinc-400 transition hover:text-zinc-100">About</a></li>
                        <li><a href="/contact" class="text-zinc-400 transition hover:text-zinc-100">Contact</a></li>
                        <li><a href="/contact-sales" class="text-zinc-400 transition hover:text-zinc-100">Talk to sales</a></li>
                        <li><a href="{{ route('login') }}" class="text-zinc-400 transition hover:text-zinc-100">Sign in</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
