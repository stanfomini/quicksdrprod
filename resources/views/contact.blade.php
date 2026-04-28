<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    @include('partials.head', ['title' => __('Contact — QuickSDR')])
    <meta name="description" content="Reach QuickSDR: email, phone, or book a call.">
    <meta property="og:title" content="Contact QuickSDR">
    <meta property="og:description" content="Email, phone, or schedule a walkthrough.">
    <meta property="og:image" content="/brand/qsdr-logo-light.png">
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
                <a href="/about" class="hover:text-zinc-100 transition">About</a>
                <a href="/contact" class="text-zinc-100">Contact</a>
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
            <h1 class="text-5xl font-semibold tracking-tight text-zinc-100 sm:text-6xl">
                Get in touch.
            </h1>
            <p class="mt-6 mx-auto max-w-2xl text-lg leading-relaxed text-zinc-400 sm:text-xl">
                Fastest way to reach us is phone. Second fastest is email. Third is the form below —
                we read it, but we prefer to talk to you.
            </p>
        </div>
    </section>

    {{-- Contact methods grid --}}
    <section class="bg-zinc-900 py-20">
        <div class="mx-auto max-w-5xl px-6">
            <div class="grid gap-6 md:grid-cols-3">

                {{-- Phone --}}
                <a href="tel:+17573433032" class="group rounded-2xl border border-zinc-800 bg-zinc-900/50 p-8 transition hover:border-cyan-500/50 hover:bg-zinc-900">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="phone" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">Call us</flux:heading>
                    <p class="mt-1 text-sm text-zinc-500">Tap to dial.</p>
                    <p class="mt-4 font-mono text-cyan-300 group-hover:text-cyan-200">(757) 343-3032</p>
                    <p class="mt-2 text-xs text-zinc-500">Mon–Fri · 9am – 6pm ET</p>
                </a>

                {{-- Email --}}
                <a href="mailto:matthew@scriptandautomations.com" class="group rounded-2xl border border-zinc-800 bg-zinc-900/50 p-8 transition hover:border-cyan-500/50 hover:bg-zinc-900">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="envelope" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">Email</flux:heading>
                    <p class="mt-1 text-sm text-zinc-500">Usually answered same day.</p>
                    <p class="mt-4 font-mono text-sm text-cyan-300 break-all group-hover:text-cyan-200">matthew@scriptandautomations.com</p>
                    <p class="mt-2 text-xs text-zinc-500">Reply goes straight to Matthew.</p>
                </a>

                {{-- Book a call --}}
                <a href="/contact-sales" class="group rounded-2xl border border-zinc-800 bg-zinc-900/50 p-8 transition hover:border-cyan-500/50 hover:bg-zinc-900">
                    <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-cyan-500/20">
                        <flux:icon name="calendar-days" class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-200">Book a call</flux:heading>
                    <p class="mt-1 text-sm text-zinc-500">For sales, custom builds, or demos.</p>
                    <p class="mt-4 text-cyan-300 group-hover:text-cyan-200">Open the sales form →</p>
                    <p class="mt-2 text-xs text-zinc-500">We'll reach out within one business day.</p>
                </a>

            </div>
        </div>
    </section>

    {{-- FAQ-lite --}}
    <section class="bg-zinc-950 py-20">
        <div class="mx-auto max-w-3xl px-6">
            <flux:heading size="xl" class="text-zinc-100 text-center">Before you reach out&hellip;</flux:heading>
            <flux:text class="mt-3 text-center text-zinc-400">
                A few things we get asked a lot. If your question fits one of these, skip the form.
            </flux:text>

            <div class="mt-10 space-y-3">
                @foreach ([
                    ["I want to sign up but I need help with Twilio A2P / AI setup.",
                     "That's what Tier 2 and Tier 3 are for — pick one on the signup page, we'll follow up to schedule a kickoff call, and handle the setup with you."],
                    ["I have a custom integration or AI agent build in mind.",
                     "Use the Talk to sales form — it routes a custom-build inquiry straight to us. \$160/hr for custom AI/automation work."],
                    ["I'm already a customer and something's broken.",
                     "Email us directly, or call — if it's urgent, phone is fastest. Don't use the Talk to sales form for bugs; it goes to the sales queue."],
                    ["Can you give me a demo?",
                     "Sure — book a call via the Talk to sales link above. Demos are usually 30 minutes on Zoom; we'll walk through the live product with your use case in mind."],
                ] as [$q, $a])
                    <details class="group rounded-xl border border-zinc-800 bg-zinc-900/40 px-5 py-4 [&[open]]:bg-zinc-900/70">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-zinc-100">
                            <span class="font-medium">{{ $q }}</span>
                            <flux:icon name="chevron-down" class="size-5 text-zinc-500 transition group-open:rotate-180 group-open:text-cyan-400" />
                        </summary>
                        <div class="mt-3 text-zinc-400">{{ $a }}</div>
                    </details>
                @endforeach
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
                        <li><a href="{{ route('privacy') }}" class="text-zinc-400 transition hover:text-zinc-100">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="text-zinc-400 transition hover:text-zinc-100">Terms of Service</a></li>
                        <li><a href="{{ route('login') }}" class="text-zinc-400 transition hover:text-zinc-100">Sign in</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
