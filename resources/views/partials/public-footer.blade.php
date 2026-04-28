<footer class="border-t border-zinc-200 bg-zinc-100 py-16">
    <div class="mx-auto max-w-6xl px-6">
        <div class="grid gap-10 md:grid-cols-4">
            {{-- Brand --}}
            <div class="md:col-span-1">
                <a href="{{ route('home') }}" class="inline-block" aria-label="QuickSDR home">
                    <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
                </a>
                <p class="mt-4 max-w-xs text-sm text-zinc-600">
                    Multi-line power dialer + AI call intelligence on a dedicated server. Build a channel that works.
                </p>
                <p class="mt-6 text-xs text-zinc-500">&copy; {{ date('Y') }} QuickSDR. All rights reserved.</p>
            </div>

            {{-- Use cases --}}
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-700">Use cases</h4>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('for-sales-teams') }}" class="text-zinc-600 transition hover:text-zinc-900">For Sales Teams</a></li>
                    <li><a href="{{ route('for-agencies') }}" class="text-zinc-600 transition hover:text-zinc-900">For Agencies &amp; VAs</a></li>
                    <li><a href="{{ route('home') }}#features" class="text-zinc-600 transition hover:text-zinc-900">Features</a></li>
                    <li><a href="{{ route('home') }}#pricing" class="text-zinc-600 transition hover:text-zinc-900">Pricing</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-700">Company</h4>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('about') }}" class="text-zinc-600 transition hover:text-zinc-900">About</a></li>
                    <li><a href="{{ route('contact') }}" class="text-zinc-600 transition hover:text-zinc-900">Contact</a></li>
                    <li><a href="{{ route('contact-sales') }}" class="text-zinc-600 transition hover:text-zinc-900">Talk to sales</a></li>
                    <li><a href="{{ route('home') }}#faq" class="text-zinc-600 transition hover:text-zinc-900">FAQ</a></li>
                </ul>
            </div>

            {{-- Legal --}}
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wider text-zinc-700">Legal</h4>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('privacy') }}" class="text-zinc-600 transition hover:text-zinc-900">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-zinc-600 transition hover:text-zinc-900">Terms of Service</a></li>
                    <li><a href="{{ route('login') }}" class="text-zinc-600 transition hover:text-zinc-900">Sign in</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
