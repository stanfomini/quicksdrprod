<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => __('Privacy Policy · QuickSDR')])

    <meta name="description" content="QuickSDR privacy policy: what data we collect, how Twilio and Stripe are used, the single-tenant server architecture, and your data rights.">
    <meta name="robots" content="index, follow">

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

        .qsdr-prose h2 { margin-top: 2.5em; margin-bottom: 0.6em; font-size: 1.5rem; font-weight: 600; color: rgb(24 24 27); letter-spacing: -0.015em; }
        .qsdr-prose h3 { margin-top: 1.8em; margin-bottom: 0.4em; font-size: 1.15rem; font-weight: 600; color: rgb(24 24 27); }
        .qsdr-prose p  { margin-top: 1em; margin-bottom: 1em; line-height: 1.75; }
        .qsdr-prose ul { margin-top: 0.75em; margin-bottom: 1em; padding-left: 1.5rem; list-style-type: disc; }
        .qsdr-prose ul li { margin-top: 0.35em; line-height: 1.65; }
        .qsdr-prose strong { color: rgb(24 24 27); font-weight: 600; }
        .qsdr-prose a  { color: rgb(14 116 144); text-decoration: underline; text-decoration-color: rgba(6, 182, 212, 0.4); text-underline-offset: 3px; }
        .qsdr-prose a:hover { color: rgb(8 145 178); }
        .qsdr-prose .callout { margin: 1.5em 0; border-left: 3px solid rgb(6 182 212); background: rgb(236 254 255); padding: 1em 1.25em; border-radius: 0.25rem; }
        .qsdr-prose .callout p { margin: 0.25em 0; }
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
                <a href="{{ route('for-sales-teams') }}" class="transition hover:text-zinc-900">For Sales Teams</a>
                <a href="{{ route('for-agencies') }}" class="transition hover:text-zinc-900">For Agencies</a>
                <a href="{{ route('home') }}#pricing" class="transition hover:text-zinc-900">Pricing</a>
                <a href="{{ route('home') }}#faq" class="transition hover:text-zinc-900">FAQ</a>
            </nav>
            <div class="flex items-center gap-3">
                <flux:button href="/signup" variant="primary" size="sm" icon-trailing="arrow-right">Start free trial</flux:button>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="dark qsdr-glow relative overflow-hidden bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 py-16 text-zinc-100">
        <div class="relative z-10 mx-auto max-w-3xl px-6">
            <span class="text-xs font-semibold uppercase tracking-widest text-cyan-300">Legal</span>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl">Privacy Policy</h1>
            <p class="mt-4 text-lg text-zinc-300">
                What data QuickSDR collects, how it flows through Twilio, Stripe, and your AI provider, and what happens to it on your dedicated server.
            </p>
            <p class="mt-6 text-sm text-zinc-400">Effective date: {{ date('F j, Y') }}</p>
        </div>
    </section>

    {{-- Body --}}
    <article class="bg-white py-16">
        <div class="qsdr-prose mx-auto max-w-3xl px-6 text-base text-zinc-700 sm:text-lg">

            <p>
                This Privacy Policy explains how QuickSDR (operated by Script &amp; Automations LLC, "we", "us") collects, uses, and protects information when you use the QuickSDR platform at quicksdr.com and the dedicated server we provision for your account.
            </p>

            <div class="callout">
                <p><strong>The short version.</strong> Your QuickSDR app runs on a single-tenant server we provision for you. Your call data, contacts, recordings, and transcripts live on <em>your</em> server — they are not pooled with other customers. We use Twilio for telephony and Stripe for billing, and you connect your own AI provider keys. You own your data and can export it at any time.</p>
            </div>

            <h2>1. Who runs this site, and who owns your server</h2>

            <p>
                QuickSDR is operated by Script &amp; Automations LLC. Our marketing and signup flow runs at quicksdr.com. When you provision a tier that includes a server (Tiers 1–3), we create a dedicated, single-tenant cloud server for your account through our infrastructure provider (currently DigitalOcean, deployed and managed via Laravel Forge).
            </p>
            <p>
                <strong>That server is yours.</strong> The application database, contact lists, call logs, recordings, transcripts, and AI scoring outputs all live on that server only. We do not aggregate your call data with other customers' data. Other QuickSDR customers cannot access your server.
            </p>

            <h2>2. What data we collect</h2>

            <h3>Information you give us at signup</h3>
            <ul>
                <li>Account contact details: name, work email, phone number, company name.</li>
                <li>Billing details: collected and stored by Stripe (see Section 5). We do not store full credit card numbers.</li>
                <li>For Tier 2 + Tier 3 setup, we may collect additional context (your use case, lead sources, client roster, team size) during the strategy call.</li>
            </ul>

            <h3>Information generated when you use the app</h3>
            <ul>
                <li>Contact records you import or create (names, phone numbers, email addresses, custom fields).</li>
                <li>Call records: outbound and inbound dial attempts, duration, disposition, recordings (if enabled).</li>
                <li>Transcripts and AI scoring outputs generated from those calls.</li>
                <li>Agent activity: who logged in, what queues they worked, calls per session.</li>
                <li>Standard application logs: IP address, browser user-agent, requested URLs, error traces — used for operational debugging.</li>
            </ul>

            <h3>Information from the people you call</h3>
            <p>
                When your team calls or receives a call from a contact, the platform records call metadata (numbers, timestamps, duration), and — if you have enabled recordings or transcription — the audio of the call and a text transcript. <strong>You are responsible for ensuring you have legal basis to record and process that data</strong> (consent, two-party consent where required, lawful business purpose, etc.). See Section 7 on compliance.
            </p>

            <h2>3. How Twilio is used</h2>

            <p>
                QuickSDR uses <a href="https://www.twilio.com/legal/privacy" target="_blank" rel="noopener">Twilio</a> as the telephony carrier for all outbound and inbound calls and SMS. <strong>You connect your own Twilio account to QuickSDR</strong> using API credentials you create in your Twilio console. Twilio is the carrier of record for your calls; QuickSDR orchestrates the dialing logic on top of your Twilio account.
            </p>

            <p>What this means in practice:</p>
            <ul>
                <li>Phone numbers you purchase appear in <em>your</em> Twilio account, are billed to <em>your</em> Twilio account, and stay yours if you leave QuickSDR.</li>
                <li>A2P 10DLC, brand, and campaign registrations live in your Twilio account.</li>
                <li>Twilio receives the destination phone numbers, dial timestamps, and call audio (per Twilio's normal carrier role).</li>
                <li>Twilio's privacy practices are governed by Twilio's Privacy Notice. We recommend you review it.</li>
                <li>Call recordings, when enabled, are stored on the recording storage you configure (your Twilio recording storage, or your dedicated QuickSDR server).</li>
            </ul>

            <h2>4. How AI providers are used</h2>

            <p>
                Real-time call transcription and lead-scoring features are powered by AI providers you choose (currently OpenAI, Anthropic, Google Gemini, and any OpenAI-compatible endpoint). <strong>You provide your own API keys</strong> — the audio and transcripts go directly from your dedicated server to the provider you've configured.
            </p>
            <ul>
                <li>QuickSDR does not pool call audio or transcripts across customers.</li>
                <li>QuickSDR does not relay AI calls through a centralized service we control — your server makes API calls directly to the provider you configured.</li>
                <li>The data-handling and retention practices of your AI provider are governed by that provider's terms (e.g., OpenAI, Anthropic, Google). We recommend you review them.</li>
            </ul>

            <h2>5. How Stripe is used (billing only)</h2>

            <p>
                We use <a href="https://stripe.com/privacy" target="_blank" rel="noopener">Stripe</a> exclusively for billing — subscription payments, setup fees, and (for Tier 4) hourly invoicing. When you sign up:
            </p>
            <ul>
                <li>You enter your card details into Stripe's hosted checkout — those details go directly to Stripe, not through QuickSDR.</li>
                <li>We store only a Stripe customer ID and a subscription ID on our orchestrator (so we can manage your billing). We do not store your card number, CVV, or full card metadata.</li>
                <li>Stripe handles PCI compliance for card data.</li>
                <li>Invoices, payment receipts, and refund records are accessible through the Stripe Customer Portal we link to from your dashboard.</li>
            </ul>

            <h2>6. Where your data is stored</h2>

            <ul>
                <li><strong>Application data</strong> (contacts, call logs, transcripts, recordings, AI scores) lives on your dedicated server. The server is provisioned in our infrastructure provider's data center (typically a US region unless agreed otherwise during Tier 2+ setup).</li>
                <li><strong>Account / billing metadata</strong> (which tier you're on, which server is yours, your Stripe customer ID) lives on the QuickSDR orchestrator at quicksdr.com.</li>
                <li><strong>Backups</strong> of your dedicated server are taken on a routine basis by our infrastructure provider for operational continuity. Backups inherit the same single-tenant isolation as your live data.</li>
            </ul>

            <h2>7. Compliance and your responsibilities</h2>

            <p>
                QuickSDR ships with TCPA-aware features (per-timezone calling windows, abandonment-rate throttling, AMD whitelisting) and supports A2P 10DLC registration through Twilio. <strong>However, you are the data controller for the calls and contacts you process</strong> — meaning compliance with the laws that apply to your calling activity is your responsibility.
            </p>
            <p>This includes (without limitation):</p>
            <ul>
                <li>The Telephone Consumer Protection Act (TCPA) and FCC consent rules in the US.</li>
                <li>State-level laws around two-party recording consent, do-not-call lists, and cold-calling restrictions.</li>
                <li>GDPR, CCPA, CPRA, and any other privacy laws applicable to your contacts and your customers' contacts (especially relevant for agency/multi-tenant use).</li>
                <li>Industry-specific rules (HIPAA for healthcare contacts, FCRA for credit-related calls, etc.).</li>
            </ul>
            <p>
                We provide tooling to help you stay compliant. We do not provide legal advice. Consult counsel for your specific operation.
            </p>

            <h2>8. Cookies and analytics</h2>

            <p>
                The marketing site at quicksdr.com uses minimal cookies — primarily a session cookie and a CSRF token used by the signup flow. We do not run third-party advertising or behavioral-tracking cookies on the marketing site.
            </p>
            <p>
                Your dedicated app server uses a session cookie for authenticated agent users. You may add analytics or other tracking on your own dashboard if you choose; we do not inject any.
            </p>

            <h2>9. Data sharing</h2>

            <p>We share data with the following categories of third parties, and only as needed to operate the service:</p>
            <ul>
                <li><strong>Twilio</strong> — for telephony (per your configured Twilio account).</li>
                <li><strong>Stripe</strong> — for billing.</li>
                <li><strong>Your chosen AI provider</strong> — for transcription and scoring.</li>
                <li><strong>Infrastructure provider</strong> (DigitalOcean, via Laravel Forge) — for hosting your dedicated server.</li>
                <li><strong>Email provider</strong> — for transactional emails like password resets and billing receipts.</li>
            </ul>
            <p>
                We do not sell your data, and we do not share it with advertisers, data brokers, or any third party for marketing purposes.
            </p>
            <p>
                We may disclose data if required by valid legal process (subpoena, court order). Where lawfully permitted, we will notify you first.
            </p>

            <h2>10. Data retention and export</h2>

            <ul>
                <li>While your subscription is active, your data is retained on your dedicated server.</li>
                <li>If you cancel, your data remains exportable for <strong>7 days</strong> after cancellation. After 7 days, the dedicated server and its contents are decommissioned.</li>
                <li>You can export contacts, call logs, transcripts, and recordings at any time through the dashboard.</li>
                <li>Account / billing metadata (Stripe IDs, signup record) is retained for our financial records as long as required by law.</li>
            </ul>

            <h2>11. Security</h2>

            <p>We follow standard practices for the architecture we run:</p>
            <ul>
                <li>TLS / HTTPS for all traffic to and from your dedicated server and the orchestrator.</li>
                <li>Server access restricted to our deploy automation and on-call engineers; no shared customer access.</li>
                <li>Application-level access controls (per-agent login, role-based permissions on the dashboard).</li>
                <li>Secrets (Twilio + AI keys) stored encrypted at rest in your dedicated server's environment.</li>
                <li>Routine security updates applied to the underlying server.</li>
            </ul>
            <p>
                No system is perfectly secure. If you believe you've discovered a security issue, email us at <a href="mailto:matthew@scriptandautomations.com">matthew@scriptandautomations.com</a>.
            </p>

            <h2>12. Your rights</h2>

            <p>
                Depending on your jurisdiction, you may have rights to access, correct, delete, or export the personal data we hold about you, and to object to certain processing. Because most of your data lives on your own dedicated server, you can typically exercise these rights directly through the application. For account-level data we hold on the orchestrator, contact us at <a href="mailto:matthew@scriptandautomations.com">matthew@scriptandautomations.com</a>.
            </p>

            <h2>13. Children</h2>

            <p>
                QuickSDR is a B2B platform. The service is not directed to anyone under the age of 18 and we do not knowingly collect personal information from minors.
            </p>

            <h2>14. Changes to this policy</h2>

            <p>
                We may update this policy from time to time. The effective date at the top of the page reflects the most recent version. Material changes will be communicated via email to active customers.
            </p>

            <h2>15. Contact</h2>

            <p>
                Questions about this privacy policy?
                Email <a href="mailto:matthew@scriptandautomations.com">matthew@scriptandautomations.com</a>
                or write to Script &amp; Automations LLC at the address on file with our state of incorporation.
            </p>

            <div class="callout">
                <p><strong>Not legal advice.</strong> This document describes QuickSDR's data handling practices in plain terms. It is not legal advice and should not be relied on as such. For obligations specific to your business, consult an attorney licensed in your jurisdiction.</p>
            </div>
        </div>
    </article>

    {{-- Footer --}}
    @include('partials.public-footer')

    @fluxScripts
</body>
</html>
