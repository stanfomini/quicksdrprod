<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => __('For Sales Teams — Build a dedicated SDR channel · QuickSDR')])

    <meta name="description" content="One dedicated BDR with a clean channel adds 35–50 deals per month per location — without forcing your existing sales team to do more. The QuickSDR playbook for operators.">
    <meta property="og:title" content="For Sales Teams — Build a dedicated SDR channel">
    <meta property="og:description" content="Add 35–50 deals per month per location with one focused BDR — without stretching the team you already have.">
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

        /* Lead source pill highlight cycle */
        @keyframes qsdr-source-active {
            0%, 24%   { background-color: rgb(24 24 27); border-color: rgb(63 63 70); color: rgb(212 212 216); }
            32%, 64%  { background-color: rgb(8 47 73);  border-color: rgb(14 116 144); color: rgb(165 243 252); }
            72%, 100% { background-color: rgb(24 24 27); border-color: rgb(63 63 70); color: rgb(212 212 216); }
        }
        .qsdr-source       { animation: qsdr-source-active 6s ease-in-out infinite; }
        .qsdr-source-d-1   { animation-delay: 0.0s; }
        .qsdr-source-d-2   { animation-delay: 2.0s; }
        .qsdr-source-d-3   { animation-delay: 4.0s; }

        /* BDR avatar pulse */
        @keyframes qsdr-bdr-pulse {
            0%, 100% { transform: scale(1);    box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.50); }
            50%      { transform: scale(1.05); box-shadow: 0 0 0 12px rgba(6, 182, 212, 0); }
        }
        .qsdr-bdr-pulse { animation: qsdr-bdr-pulse 2s ease-in-out infinite; }

        /* Closed-deal feed — items fade in + slide up sequentially */
        @keyframes qsdr-deal-in {
            0%, 5%    { opacity: 0; transform: translateY(8px); }
            18%, 100% { opacity: 1; transform: translateY(0); }
        }
        .qsdr-deal       { animation: qsdr-deal-in 6s ease-out infinite; opacity: 0; }
        .qsdr-deal-d-1   { animation-delay: 0.5s; }
        .qsdr-deal-d-2   { animation-delay: 1.7s; }
        .qsdr-deal-d-3   { animation-delay: 2.9s; }
        .qsdr-deal-d-4   { animation-delay: 4.1s; }

        /* Counter flash on each tick */
        @keyframes qsdr-tally-flash {
            0%, 8%    { color: rgb(212 212 216); transform: scale(1); }
            12%, 20%  { color: rgb(34 197 94);   transform: scale(1.12); }
            28%, 100% { color: rgb(212 212 216); transform: scale(1); }
        }
        .qsdr-tally-flash { animation: qsdr-tally-flash 1.5s ease-out infinite; }

        /* Flow arrow */
        @keyframes qsdr-flow {
            0%, 100% { opacity: 0.4; transform: translateX(0); }
            50%      { opacity: 1;   transform: translateX(4px); }
        }
        .qsdr-flow { animation: qsdr-flow 2s ease-in-out infinite; }

        @media (prefers-reduced-motion: reduce) {
            .qsdr-source, .qsdr-bdr-pulse, .qsdr-deal, .qsdr-tally-flash, .qsdr-flow {
                animation: none; opacity: 1;
            }
        }

        /* Prose styling for the article */
        .qsdr-prose p          { margin-top: 1.1em; margin-bottom: 1.1em; line-height: 1.75; }
        .qsdr-prose ul         { margin-top: 1em; margin-bottom: 1em; padding-left: 1.5rem; list-style-type: disc; }
        .qsdr-prose ul li      { margin-top: 0.35em; line-height: 1.6; }
        .qsdr-prose strong     { color: rgb(24 24 27); font-weight: 600; }
        .qsdr-prose h3         { margin-top: 2.5em; margin-bottom: 0.6em; font-size: 1.5rem; font-weight: 600; color: rgb(24 24 27); letter-spacing: -0.015em; }
        .qsdr-prose blockquote { margin: 1.8em 0; border-left: 3px solid rgb(6 182 212); padding: 0.5em 0 0.5em 1.25em; font-size: 1.15rem; font-style: italic; color: rgb(63 63 70); }
    </style>
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased">

    {{-- Header --}}
    <header class="sticky top-0 z-50 border-b border-zinc-200 bg-white/85 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="QuickSDR home">
                <img src="/brand/qsdr-logo.svg" alt="QuickSDR" class="h-8 w-auto">
            </a>

            <nav class="hidden items-center gap-7 text-sm text-zinc-600 md:flex" aria-label="Primary">
                <a href="{{ route('for-sales-teams') }}" class="font-semibold text-zinc-900">For Sales Teams</a>
                <a href="{{ route('for-agencies') }}" class="transition hover:text-zinc-900">For Agencies</a>
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

    {{-- ──────────── 1. Hero (DARK) ──────────── --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100">
        <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-6 py-20 md:py-24 lg:grid-cols-[1.15fr_1fr] lg:gap-16 lg:py-28">
            <div class="flex flex-col justify-center">
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-cyan-400/40 bg-cyan-500/10 px-3 py-1 text-xs font-medium text-cyan-200">
                    <flux:icon name="chart-bar" class="h-3.5 w-3.5" />
                    For sales teams &amp; operators
                </span>

                <h1 class="mt-6 text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                    One dedicated BDR.<br>
                    One clean channel.<br>
                    <span class="bg-gradient-to-r from-blue-400 via-cyan-300 to-blue-300 bg-clip-text text-transparent">
                        35–50 more deals / month per location.
                    </span>
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-zinc-200">
                    Your existing sales floor is already stretched. The fix isn't to ask them to do more — it's to build a clean, dedicated channel that runs alongside the old one. Here's how operators are doing it, with the math behind it.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#article" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-cyan-500/20 transition hover:from-blue-500 hover:to-cyan-400">
                        Read the playbook
                        <flux:icon name="arrow-down" class="h-4 w-4" />
                    </a>
                    <flux:button href="/contact-sales" variant="ghost">
                        Tell Matt about your bottleneck
                    </flux:button>
                </div>
            </div>

            {{-- SDR pipeline graphic --}}
            <div class="flex items-center justify-center" aria-hidden="true">
                <div class="w-full max-w-sm rounded-2xl border border-zinc-800 bg-zinc-900/70 p-5 shadow-2xl backdrop-blur">
                    <div class="mb-4 flex items-center justify-between text-xs">
                        <span class="font-medium text-zinc-200">Acquisition channel · live</span>
                        <span class="rounded-md bg-zinc-800 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-zinc-300">Wk 6</span>
                    </div>

                    <div class="grid grid-cols-[1fr_auto_auto] items-center gap-3">
                        {{-- Lead sources --}}
                        <div class="flex flex-col gap-1.5">
                            <div class="qsdr-source qsdr-source-d-1 rounded-md border px-2.5 py-1.5 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span>Online offer</span>
                                    <span class="font-mono">12/d</span>
                                </div>
                            </div>
                            <div class="qsdr-source qsdr-source-d-2 rounded-md border px-2.5 py-1.5 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span>Walk-in lead</span>
                                    <span class="font-mono">4/d</span>
                                </div>
                            </div>
                            <div class="qsdr-source qsdr-source-d-3 rounded-md border px-2.5 py-1.5 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span>Follow-up</span>
                                    <span class="font-mono">2/d</span>
                                </div>
                            </div>
                        </div>

                        <flux:icon name="arrow-right" class="qsdr-flow h-4 w-4 text-cyan-300" />

                        {{-- BDR --}}
                        <div class="flex flex-col items-center gap-1">
                            <div class="qsdr-bdr-pulse flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-cyan-400 text-xs font-bold text-white shadow-md">
                                BDR
                            </div>
                            <span class="font-mono text-[10px] text-zinc-400">125 calls/d</span>
                        </div>
                    </div>

                    {{-- Deal feed --}}
                    <div class="mt-5 border-t border-zinc-800 pt-4">
                        <div class="flex items-center justify-between text-[11px] text-zinc-400">
                            <span class="uppercase tracking-wider">Deals this week</span>
                            <span class="qsdr-tally-flash font-mono text-2xl font-bold">12</span>
                        </div>
                        <div class="mt-2 space-y-1.5">
                            <div class="qsdr-deal qsdr-deal-d-1 flex items-center justify-between rounded-md bg-emerald-500/10 px-2.5 py-1.5 text-[11px] ring-1 ring-emerald-500/30">
                                <span class="text-emerald-200">Acme — Closed</span>
                                <span class="font-mono text-zinc-400">+1</span>
                            </div>
                            <div class="qsdr-deal qsdr-deal-d-2 flex items-center justify-between rounded-md bg-emerald-500/10 px-2.5 py-1.5 text-[11px] ring-1 ring-emerald-500/30">
                                <span class="text-emerald-200">Northbound — Closed</span>
                                <span class="font-mono text-zinc-400">+1</span>
                            </div>
                            <div class="qsdr-deal qsdr-deal-d-3 flex items-center justify-between rounded-md bg-emerald-500/10 px-2.5 py-1.5 text-[11px] ring-1 ring-emerald-500/30">
                                <span class="text-emerald-200">Helix — Closed</span>
                                <span class="font-mono text-zinc-400">+1</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────── 2. TL;DR results callout (LIGHT) ──────────── --}}
    <section class="border-b border-zinc-200 bg-zinc-50 py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-700">The tl;dr</span>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                    Process tweaks added 15%. One dedicated channel added 35–50 deals/mo.
                </h2>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['+15%',         'lift from centralizing pricing', 'The smart-on-paper change. Helped — but didn\'t move the needle.'],
                    ['100–150',      'calls + texts per day, per BDR', 'What one focused person can sustainably do with the right dialer.'],
                    ['+35–50',       'incremental deals per month, per location', 'Steady-state result once the BDR ramps to a 7–10% close rate.'],
                ] as [$stat, $unit, $desc])
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-4xl font-bold tracking-tight text-transparent">
                            {{ $stat }}
                        </div>
                        <div class="mt-1 text-sm font-medium text-zinc-900">{{ $unit }}</div>
                        <p class="mt-3 text-sm text-zinc-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────── 3. Article (LIGHT, prose) ──────────── --}}
    <section id="article" class="bg-white py-20">
        <div class="mx-auto max-w-3xl px-6">
            <div class="mb-10">
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-700">Field notes from Matt</span>
                <h2 class="mt-3 text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">
                    How one simple channel fix added 35–50 deals per month per location.
                </h2>
                <p class="mt-4 text-lg text-zinc-600">
                    Without more ads. Without more sales reps.
                </p>
            </div>

            <div class="qsdr-prose text-lg text-zinc-700">
                <p>
                    I used to work inside a company that grew fast by acquiring smaller operations and their inventory. The lesson that changed how I think about scaling wasn't complicated. It was this:
                </p>

                <blockquote>
                    When your current systems hit a wall — culture, capacity, or old habits — you don't force the old machine to do more. You build a clean, separate channel that runs alongside it.
                </blockquote>

                <p>
                    That sounds obvious once you hear it. Most companies never actually do it.
                </p>

                <h3>The situation we all recognize</h3>

                <p>
                    Here's the exact setup I've seen — and lived — at multiple companies:
                </p>

                <ul>
                    <li>10 to 50 leads coming in every day</li>
                    <li>25-minute average response time</li>
                    <li>Four lead sources feeding the funnel: inventory acquisition, purchased leads, paid ads, and follow-ups with existing customers</li>
                </ul>

                <p>
                    The team had roughly 40 salespeople handling about 150 leads per day. On paper it looked busy:
                </p>

                <ul>
                    <li>Reps made 20–50 calls each per day (~2,000 outbound calls daily across the team)</li>
                    <li>That produced about <strong>60 appointments per day</strong> across the whole team</li>
                    <li>At a 30% close rate, they closed roughly <strong>18 deals per day</strong></li>
                </ul>

                <p>
                    Predictable? Yes. Growing the way leadership wanted? Not even close.
                </p>

                <p>
                    The company's stated strategy was clear: grow by aggressively acquiring more inventory. Yet only about <strong>5% of appointments</strong> were actually focused on acquisition — that's roughly 3 acquisition appointments a day across the whole company. Even when reps tried, the close rate on those deals was only 50%. So we were closing maybe <strong>1.5 acquisition deals per day</strong>, in a company whose growth plan depended on acquisition.
                </p>

                <p>
                    The bottleneck wasn't leads. It was focus.
                </p>

                <h3>What leadership tried (and why it only moved the needle 15%)</h3>

                <p>
                    The team decided to centralize acquisition pricing. The goal was simple: free up local managers, let them send 10× more offers, and finally advertise acquisition at scale.
                </p>

                <p>
                    It made sense on paper. We modeled a 60% lift.
                </p>

                <p>
                    What actually happened? Acquisition increased <strong>15%</strong>.
                </p>

                <p>
                    Not the jump we needed. Not enough to move a 600-unit inventory goal. We were still stuck at the same crossroads.
                </p>

                <p>
                    That's when the real shift happened.
                </p>

                <h3>The fix that actually worked</h3>

                <p>
                    Instead of asking the existing sales team to do more, we brought in <strong>one dedicated specialist</strong> per location — a BDR whose only job was to own the acquisition channel:
                </p>

                <ul>
                    <li>Contact every lead that came in with an online offer</li>
                    <li>Follow up with anyone who had shown interest recently</li>
                    <li>Keep the acquisition conversations moving even on busy in-person days</li>
                </ul>

                <p>
                    They made <strong>100–150 calls and texts per day, per location</strong>. Here's what the numbers looked like:
                </p>

                <p>
                    <strong>Week 1.</strong> 12 acquisition leads/day × 6 days = 72 leads per store. At a 3% close rate, that's about 2 deals per store. Across 3 stores: 6 closed deals.
                </p>

                <p>
                    <strong>Week 2.</strong> 18 leads/day × 6 days = 108 leads per store. The BDR had started learning the playbook — close rate jumped to 5%. That's 5–6 deals per store. Across 3 stores: 16 closed deals.
                </p>

                <p>
                    <strong>By weeks 6–8</strong>, with the BDR fully ramped and a 7–10% close rate on roughly 400 leads/month per store, one location was purchasing <strong>35–50 more units per month than before</strong>. That wasn't magic. It was focus.
                </p>

                <h3>What we actually changed (the three simple steps)</h3>

                <ol class="mt-4 space-y-3 pl-6 [list-style-type:decimal] marker:font-semibold marker:text-cyan-700">
                    <li><strong>Streamlined pricing</strong> so the specialist could move quickly without constant approvals.</li>
                    <li><strong>Trained one dedicated person</strong> whose only responsibility was acquisition — no split focus, no distraction.</li>
                    <li><strong>Made the channel predictable</strong> — same daily process, same tracking, same ownership.</li>
                </ol>

                <p>
                    That's it. One new channel. One owner. Clear process.
                </p>

                <blockquote>
                    Success isn't about having the perfect strategy. It's about implementing the right one, one step at a time.
                </blockquote>

                <h3>The real lesson (and why most companies stay stuck)</h3>

                <p>
                    Your current systems are already working — for the people who run them every day. But they often aren't built to scale the way your business plan requires.
                </p>

                <p>
                    When you look at growth, the first place to look is the systems you already have. Ask yourself: <em>"Is this truly scalable, or has it just become comfortable?"</em>
                </p>

                <p>
                    The companies that break through are the ones that stop forcing the old process to do something it was never designed for — and instead create a clean, dedicated channel that runs every single day.
                </p>

                <h3>Why I built quicksdr.com</h3>

                <p>
                    I've watched too many good businesses hit this exact wall: plenty of leads, a clear growth goal, and a sales team that's already stretched thin. They don't need more tools that complicate things. They need a simple way to run this exact strategy — a dedicated channel that actually works.
                </p>

                <p>
                    That's why I built quicksdr.com. It turns this approach into something any company can implement without guessing or starting from scratch.
                </p>

                <p>
                    If you're facing a similar bottleneck — inventory acquisition, lead recovery, follow-ups, any constrained channel — I'd genuinely like to hear about it. <a href="/contact-sales" class="font-semibold text-cyan-700 underline decoration-cyan-300/60 underline-offset-4 hover:text-cyan-600">Send me a DM.</a> Tell me where you're stuck. I read every message.
                </p>

                <p>
                    You already have the pieces. You just need the right channel running beside them.
                </p>

                <p class="mt-8 text-zinc-500">— Matt</p>
            </div>
        </div>
    </section>

    {{-- ──────────── 4. Concrete numbers walkthrough (LIGHT, table) ──────────── --}}
    <section class="bg-zinc-50 py-20">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                    The math, week by week.
                </h2>
                <p class="mt-4 text-lg text-zinc-600">
                    Per store. The numbers above are real — here's how they compound.
                </p>
            </div>

            <div class="mt-10 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th class="px-6 py-4 font-medium">Phase</th>
                            <th class="px-6 py-4 font-medium">Leads / day</th>
                            <th class="px-6 py-4 font-medium">Close rate</th>
                            <th class="px-6 py-4 font-medium">Deals / week / store</th>
                            <th class="bg-cyan-50 px-6 py-4 font-medium text-cyan-700">Deals / month / store</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @foreach ([
                            ['Week 1 (ramp)',    '12', '3%',     '~2',  '~9'],
                            ['Week 2',           '18', '5%',     '~5',  '~22'],
                            ['Weeks 6–8 (steady)', '18', '7–10%', '~7–10', '+35–50'],
                        ] as $row)
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">{{ $row[0] }}</td>
                                <td class="px-6 py-4 text-zinc-700">{{ $row[1] }}</td>
                                <td class="px-6 py-4 text-zinc-700">{{ $row[2] }}</td>
                                <td class="px-6 py-4 text-zinc-700">{{ $row[3] }}</td>
                                <td class="bg-cyan-50 px-6 py-4 font-semibold text-cyan-800">{{ $row[4] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-6 text-center text-sm text-zinc-500">
                Math: 18 leads/day × ~22 work days = ~400 leads/month/store. At 7–10% close, that's 28–40 deals — the higher end (35–50) is what one of the locations consistently hit by month 2.
            </p>
        </div>
    </section>

    {{-- ──────────── 5. The 3-step framework (DARK band) ──────────── --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-zinc-950 py-24 text-zinc-100">
        <div class="relative z-10 mx-auto max-w-5xl px-6">
            <div class="text-center">
                <span class="text-xs font-semibold uppercase tracking-widest text-cyan-300">The 3-step framework</span>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                    What a dedicated channel actually requires.
                </h2>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['arrows-right-left', 'Streamlined process',  'Remove approval bottlenecks. The specialist needs to move at the speed of the lead, not the speed of your manager\'s inbox.'],
                    ['user-circle',       'One dedicated owner',  'No split focus. The BDR\'s only job is this one channel. Pulling them onto the floor for "just one thing" is what kills it.'],
                    ['arrow-path',        'Predictable rhythm',   'Same daily call/text targets, same tracking, same morning standup. The compounding only happens if the channel runs every day.'],
                ] as [$icon, $title, $desc])
                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 backdrop-blur">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-md shadow-cyan-500/20">
                            <flux:icon name="{{ $icon }}" class="h-5 w-5" />
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-zinc-100">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-zinc-300">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────── 6. Why this fails without the right tools (LIGHT) ──────────── --}}
    <section class="bg-white py-24">
        <div class="mx-auto max-w-4xl px-6">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                    Why this fails without the right tools.
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-600">
                    100–150 calls + texts per day per BDR is what makes the math work. A regular phone, a spreadsheet, and a CRM tab won't get you there. Here's what breaks — and what QuickSDR fixes.
                </p>
            </div>

            <div class="space-y-5">
                @foreach ([
                    ['Single-line dialing caps you at 30–40 calls/hr.',
                     'A BDR using one phone, one number at a time, can\'t physically hit 125 calls in a day. QuickSDR\'s 5-line parallel dialer gets you to 300+ dials per hour with zero added stress on the rep.'],
                    ['Spreadsheet tracking falls apart by week 2.',
                     'You can\'t see who was called, when, or why outcomes are shifting. Smart Queue tracks every dial, callback, and disposition automatically — so the daily standup actually has data to look at.'],
                    ['Without scoring, every lead looks the same.',
                     'A focused BDR is most valuable when they\'re calling the highest-intent leads first. AI scoring ranks every lead based on past conversation signals so the BDR works the right list, not just the next list.'],
                    ['TCPA + carrier flagging tank the channel by month two.',
                     'Make 125 calls/day from a single number with no compliance guardrails and you\'ll get spam-flagged inside three weeks. Per-timezone TCPA windows + auto-throttle + AMD whitelisting keep the channel running clean.'],
                ] as [$title, $desc])
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6">
                        <h3 class="text-base font-semibold text-zinc-900">{{ $title }}</h3>
                        <p class="mt-2 text-zinc-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ──────────── 7. Tier callout (LIGHT) ──────────── --}}
    <section class="bg-zinc-50 py-20">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                    Pick the implementation that fits.
                </h2>
                <p class="mt-4 text-lg text-zinc-600">
                    Most operators start with Tier 2. Multi-location? Go straight to Tier 3.
                </p>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                {{-- Tier 2 --}}
                <div class="relative flex flex-col rounded-2xl border-2 border-cyan-500 bg-white p-8 shadow-xl shadow-cyan-500/15">
                    <div class="absolute -top-3 left-8">
                        <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white shadow">
                            Most start here
                        </span>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900">Tier 2 — Setup + Use-Case Consultation</h3>
                    <p class="mt-2 text-sm text-zinc-600">
                        We'll help you design <em>your</em> dedicated channel. Strategy call, IVR + cadence design, Twilio + AI configured, and a Slack channel for week-one questions.
                    </p>
                    <div class="mt-5 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-zinc-900">$1,000</span>
                        <span class="text-sm text-zinc-600">setup &middot; + $350/mo</span>
                    </div>
                    <p class="text-sm text-cyan-700"><strong>First 2 months waived.</strong></p>

                    <ul class="mt-6 flex-1 space-y-2 text-sm">
                        @foreach ([
                            '90-min strategy call with Matt to map your bottleneck channel',
                            'BDR daily-rhythm + tracking template',
                            'Cadence + scoring tuned for your lead types',
                            'Slack channel + priority support',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-700">
                                <flux:icon name="check-circle" class="mt-0.5 h-4 w-4 flex-none text-cyan-600" />
                                <span>{{ $bullet }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <flux:button href="/signup?tier=2" variant="primary" class="mt-7 w-full" icon-trailing="arrow-right">
                        Book your strategy call
                    </flux:button>
                </div>

                {{-- Tier 3 --}}
                <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm">
                    <h3 class="text-xl font-semibold text-zinc-900">Tier 3 — Multi-Location + Strategy Partnership</h3>
                    <p class="mt-2 text-sm text-zinc-600">
                        Multiple locations or multiple use cases. We architect the rollout, train each location's BDR + manager, and stay engaged for the first 90 days.
                    </p>
                    <div class="mt-5 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-zinc-900">$3,000</span>
                        <span class="text-sm text-zinc-600">setup &middot; + $350/mo</span>
                    </div>
                    <p class="text-sm text-cyan-700"><strong>First 2 months waived.</strong></p>

                    <ul class="mt-6 flex-1 space-y-2 text-sm">
                        @foreach ([
                            'Multi-location architecture + per-store configuration',
                            'Live SDR + manager training per location',
                            'Monthly strategy session for the first 90 days',
                            'Upgraded server tier + priority support',
                        ] as $bullet)
                            <li class="flex items-start gap-2 text-zinc-700">
                                <flux:icon name="check-circle" class="mt-0.5 h-4 w-4 flex-none text-cyan-600" />
                                <span>{{ $bullet }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <flux:button href="/signup?tier=3" variant="primary" class="mt-7 w-full" icon-trailing="arrow-right">
                        Book a strategy call
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    {{-- ──────────── 8. Final CTA (DARK) ──────────── --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-gradient-to-r from-blue-700 via-blue-600 to-cyan-500 py-20">
        <div class="relative z-10 mx-auto max-w-4xl px-6 text-center">
            <h2 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                Tell Matt about your bottleneck.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-white/95">
                Inventory acquisition, lead recovery, follow-ups — whatever channel is constrained. Tell us where you're stuck and we'll scope the dedicated channel together.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="/contact-sales"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-blue-700 shadow-xl transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50">
                    Talk to Matt
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
