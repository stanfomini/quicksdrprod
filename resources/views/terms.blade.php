<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => __('Terms of Service · QuickSDR')])

    <meta name="description" content="QuickSDR Terms of Service: account terms, billing, acceptable use including TCPA-compliant calling, data ownership, warranty disclaimers.">
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
        .qsdr-prose .legal-emph { text-transform: uppercase; font-weight: 600; letter-spacing: 0.02em; }
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
            <h1 class="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl">Terms of Service</h1>
            <p class="mt-4 text-lg text-zinc-300">
                The agreement between you and QuickSDR for use of the platform, your dedicated server, and the services we provide.
            </p>
            <p class="mt-6 text-sm text-zinc-400">Effective date: {{ date('F j, Y') }}</p>
        </div>
    </section>

    {{-- Body --}}
    <article class="bg-white py-16">
        <div class="qsdr-prose mx-auto max-w-3xl px-6 text-base text-zinc-700 sm:text-lg">

            <p>
                These Terms of Service ("Terms") govern your access to and use of the QuickSDR platform, the dedicated server we provision for your account, and any related services (collectively, the "Services") provided by Quick Technologies, Inc., a Wyoming C-Corporation ("QuickSDR", "we", "us"). The Services are offered to customers across the United States. By signing up for or using the Services, you agree to these Terms regardless of the state from which you operate. If you don't agree, don't use the Services.
            </p>

            <div class="callout">
                <p><strong>The short version.</strong> You sign up for a tier, you pay your subscription, you use QuickSDR to dial and manage calls — and you do so in compliance with the laws that apply to your calling. We provide the platform; we don't dial for you and we don't make compliance decisions for you. The detailed terms below say that more carefully.</p>
            </div>

            <h2>1. Eligibility &amp; account</h2>

            <p>
                You must be at least 18 years old and legally able to enter into a contract to use the Services. If you sign up on behalf of a company or other entity, you represent that you have the authority to bind that entity to these Terms.
            </p>
            <p>
                You are responsible for safeguarding your account credentials and for all activity that occurs under your account. Notify us immediately of any unauthorized use.
            </p>

            <h2>2. Plans, fees &amp; billing</h2>

            <h3>Pricing tiers</h3>
            <p>
                Current pricing is described on our pricing page. The base tiers are:
            </p>
            <ul>
                <li><strong>Self-serve</strong> — $350/month subscription. Includes a 3-day free trial.</li>
                <li><strong>Setup + Use-Case Consultation (Tier 2)</strong> — $1,000 one-time setup fee plus $350/month subscription. The first two months of subscription are waived.</li>
                <li><strong>Multi-Location + Strategy Partnership (Tier 3)</strong> — $3,000 one-time setup fee plus $350/month subscription. The first two months of subscription are waived.</li>
                <li><strong>Custom build (Tier 4)</strong> — $160/hour, billed against work performed. No monthly subscription.</li>
            </ul>

            <h3>Billing terms</h3>
            <ul>
                <li>Subscriptions renew monthly unless you cancel.</li>
                <li>All payments are processed by Stripe. Your card data is handled by Stripe under their terms.</li>
                <li>Setup fees on Tier 2 and Tier 3 are non-refundable once the strategy call has occurred.</li>
                <li>Subscription fees are non-refundable for the current billing period; cancellation takes effect at the end of the current period.</li>
                <li>If a payment fails, your account enters a grace period. After the grace period, the subscription may be cancelled and your dedicated server may be decommissioned per Section 9.</li>
                <li>Twilio costs (numbers, A2P registration, per-minute call charges, SMS) are billed by Twilio directly to your Twilio account. AI provider costs are billed by your AI provider directly. <strong>QuickSDR does not invoice for Twilio or AI usage.</strong></li>
            </ul>

            <h2>3. Your dedicated server</h2>

            <p>
                Tiers 1–3 include a dedicated, single-tenant server provisioned for your account. While your subscription is active:
            </p>
            <ul>
                <li>The server is operated by QuickSDR on infrastructure we manage (currently DigitalOcean via Laravel Forge).</li>
                <li>The application code, your contacts, call logs, recordings, transcripts, and AI scoring outputs reside on that server.</li>
                <li>QuickSDR maintains the underlying server (security patches, runtime updates, deployments of new platform versions).</li>
                <li>You retain ownership of the application data on the server (the contacts, calls, and transcripts you create).</li>
            </ul>
            <p>
                The server itself, including the underlying compute, OS, and infrastructure, is operated by us — you do not have direct shell or root access. Custom infrastructure access can be arranged under Tier 4.
            </p>

            <h2>4. Acceptable use</h2>

            <p>
                You agree not to use the Services to:
            </p>
            <ul>
                <li>Violate any law, including but not limited to the Telephone Consumer Protection Act (TCPA), state-level recording-consent laws, do-not-call regulations, anti-spam laws (e.g., CAN-SPAM, CASL), or any other regulation applicable to your calling activity.</li>
                <li>Make unsolicited automated marketing calls or texts to recipients without the consent required by applicable law.</li>
                <li>Falsify caller-ID, spoof phone numbers in a deceptive manner, or otherwise violate STIR/SHAKEN or TRACED Act provisions.</li>
                <li>Harass, threaten, defame, or defraud anyone you contact through the platform.</li>
                <li>Process data subject to special legal regimes (e.g., HIPAA-protected health information, FCRA-regulated credit data) without first putting in place the contracts and controls those regimes require — and without notifying us so we can ensure the configuration supports those obligations.</li>
                <li>Attempt to gain unauthorized access to other customers' servers, the orchestrator, or our infrastructure.</li>
                <li>Reverse-engineer, decompile, or attempt to extract the source code of QuickSDR for the purpose of building a competing product.</li>
                <li>Resell QuickSDR as your own product. (Reselling phone-answering services to your clients <em>using</em> QuickSDR is permitted and encouraged — that's the agency use case. Reselling QuickSDR <em>itself</em> as your own platform is not.)</li>
                <li>Use the Services in a way that overloads, disrupts, or impairs the platform or any third-party service it depends on.</li>
            </ul>

            <h2>5. Compliance is your responsibility</h2>

            <p>
                QuickSDR provides tooling that supports compliant calling — per-timezone TCPA windows, abandonment-rate throttling, A2P 10DLC registration support through Twilio, recording controls, and per-tenant routing for multi-client setups. <strong>Configuration and use of these tools, and the underlying obligations they support, are your responsibility.</strong>
            </p>
            <p>
                You are the data controller and the calling party for your campaigns. We are a service provider / processor. QuickSDR does not provide legal advice. You should consult an attorney about your specific compliance obligations, especially if you operate across multiple states, internationally, or in a regulated industry.
            </p>

            <h2>6. Third-party services</h2>

            <p>
                The Services depend on third parties — most importantly:
            </p>
            <ul>
                <li><strong>Twilio</strong> for telephony. You connect your own Twilio account. Twilio's terms apply directly between you and Twilio.</li>
                <li><strong>AI providers</strong> (OpenAI, Anthropic, Google, or any OpenAI-compatible endpoint) for transcription and scoring. You provide your own API keys. The provider's terms apply directly between you and that provider.</li>
                <li><strong>Stripe</strong> for billing.</li>
                <li><strong>Infrastructure provider</strong> (currently DigitalOcean) for server hosting.</li>
            </ul>
            <p>
                We are not responsible for outages, billing disputes, data handling, or other issues that arise within these third-party services. We will, however, work with you in good faith to diagnose problems that span QuickSDR and a third party.
            </p>

            <h2>7. Data ownership &amp; export</h2>

            <p>
                You own the application data you create through your use of the Services (your contacts, call logs, transcripts, recordings, scoring outputs, and configuration). QuickSDR owns the platform itself (the application code, dashboards, and orchestration logic).
            </p>
            <p>
                You can export your data at any time through the dashboard. After cancellation, your data remains exportable for 7 days, after which the dedicated server and its contents are decommissioned (see Section 9).
            </p>

            <h2>8. Confidentiality</h2>

            <p>
                Each party agrees to protect the other's confidential information with the same degree of care it uses for its own (and no less than reasonable care). Information is "confidential" if marked as such, or if a reasonable person would understand it to be confidential under the circumstances. This includes the contents of strategy calls, technical architecture details we share, and your operational metrics.
            </p>

            <h2>9. Suspension &amp; termination</h2>

            <h3>By you</h3>
            <p>
                You may cancel your subscription at any time through the Stripe billing portal accessible from your dashboard. Cancellation is effective at the end of the current billing period.
            </p>

            <h3>By us</h3>
            <p>
                We may suspend or terminate your account if:
            </p>
            <ul>
                <li>Payment fails and is not resolved during the grace period.</li>
                <li>You materially violate these Terms (especially Section 4, acceptable use, or Section 5, compliance).</li>
                <li>Your activity poses a risk to other customers, the platform, or third parties (e.g., your dialing pattern triggers carrier-level enforcement that affects shared infrastructure).</li>
                <li>We are required to do so by law or by valid legal process.</li>
            </ul>
            <p>
                Where reasonable, we will give you notice and an opportunity to cure before terminating for material breach.
            </p>

            <h3>Effect of termination</h3>
            <ul>
                <li>You retain export access to your data for 7 days after termination.</li>
                <li>After 7 days, the dedicated server is decommissioned and its contents are deleted, except for backups held by our infrastructure provider per their normal retention schedule.</li>
                <li>Any unpaid fees become immediately due. Setup fees remain non-refundable.</li>
            </ul>

            <h2>10. Warranty disclaimer</h2>

            <p class="legal-emph">
                The services are provided "as is" and "as available" without warranties of any kind, whether express, implied, statutory, or otherwise. We disclaim, to the maximum extent permitted by law, all warranties including the implied warranties of merchantability, fitness for a particular purpose, title, and non-infringement.
            </p>
            <p>
                We do not warrant that the Services will be uninterrupted, error-free, secure against all attacks, or that any results — including pipeline outcomes, deal volume, or compliance results — will meet your expectations. The "35–50 deals per month per location" figures and other outcome statistics on our marketing pages reflect what other operators have achieved using a focused channel, not a guarantee for your business.
            </p>

            <h2>11. Limitation of liability</h2>

            <p class="legal-emph">
                To the maximum extent permitted by law, QuickSDR will not be liable for any indirect, incidental, special, consequential, exemplary, or punitive damages, or for lost profits, lost revenue, loss of data, business interruption, or loss of goodwill, arising out of or related to your use of the Services — even if we have been advised of the possibility of such damages.
            </p>
            <p class="legal-emph">
                Our aggregate liability for any claim arising out of or related to these Terms or the Services will not exceed the total fees you paid to QuickSDR in the twelve (12) months immediately preceding the event giving rise to the claim, or one hundred US dollars ($100), whichever is greater.
            </p>
            <p>
                Some jurisdictions do not allow the exclusion or limitation of certain damages; in those jurisdictions, our liability is limited to the maximum extent permitted by law.
            </p>

            <h2>12. Indemnification</h2>

            <p>
                You agree to defend, indemnify, and hold harmless QuickSDR and its officers, employees, and agents from and against any claims, damages, liabilities, costs, and expenses (including reasonable attorneys' fees) arising out of:
            </p>
            <ul>
                <li>Your use of the Services in violation of these Terms.</li>
                <li>Your violation of any law, including TCPA and similar telecommunications and privacy laws.</li>
                <li>The contacts, lists, recordings, and transcripts you process through the platform.</li>
                <li>Any dispute between you and a person you contacted using the Services.</li>
            </ul>

            <h2>13. Changes to the service</h2>

            <p>
                We may modify, add, or remove features over time. We will provide reasonable notice of material breaking changes. We may also update these Terms; the "Effective date" at the top reflects the most recent version. Material changes will be communicated via email to active customers, and continued use of the Services after the effective date constitutes acceptance.
            </p>

            <h2>14. Governing law, arbitration &amp; class-action waiver</h2>

            <h3>14.1 Governing law</h3>
            <p>
                These Terms are governed by and construed in accordance with the laws of the State of Wyoming, USA, without regard to its conflict-of-laws principles. The United Nations Convention on Contracts for the International Sale of Goods does not apply.
            </p>

            <h3>14.2 Binding arbitration</h3>
            <p class="legal-emph">
                Any dispute, claim, or controversy arising out of or relating to these Terms or the Services — including their formation, validity, breach, or termination — will be resolved exclusively by final and binding arbitration administered by the American Arbitration Association ("AAA") under its Commercial Arbitration Rules then in effect.
            </p>
            <p>
                The arbitration will be conducted by a single neutral arbitrator. The seat (legal place) of arbitration is Sheridan County, Wyoming. The arbitration may be conducted by videoconference, telephone, or by document submission, and an in-person hearing in Wyoming will be available at either party's request. The arbitrator's award is final and may be entered as a judgment in any court of competent jurisdiction. The arbitrator — and not any federal, state, or local court — has exclusive authority to decide all issues relating to scope, enforceability, and arbitrability of these Terms.
            </p>

            <h3>14.3 Class-action waiver</h3>
            <p class="legal-emph">
                You and QuickSDR agree that each may bring claims against the other only in your or its individual capacity, and not as a plaintiff or class member in any purported class, collective, consolidated, or representative proceeding. The arbitrator may not consolidate claims of more than one person and may not preside over any form of representative or class proceeding. If a court decides that this class-action waiver is unenforceable as to any particular claim, that claim (and only that claim) must be brought in court rather than arbitration; the rest of these Terms remain in force.
            </p>

            <h3>14.4 Carve-outs</h3>
            <p>
                Notwithstanding 14.2, either party may:
            </p>
            <ul>
                <li>Bring a claim in <strong>small-claims court</strong> in your home jurisdiction if the claim qualifies under that court's monetary limits and procedural rules; and</li>
                <li>Seek <strong>injunctive or equitable relief</strong> in any court of competent jurisdiction to prevent actual or threatened infringement of intellectual property or breach of confidentiality obligations, pending arbitration of the underlying dispute.</li>
            </ul>

            <h3>14.5 Fees and costs</h3>
            <p>
                Each party bears its own attorneys' fees and costs in arbitration unless the arbitrator awards them under applicable law. AAA filing fees and arbitrator compensation are allocated under the AAA Commercial Rules; QuickSDR will pay any portion of AAA fees that exceeds what you would have paid in court for the same claim, where required by law.
            </p>

            <h3>14.6 30-day right to opt out</h3>
            <p>
                You may opt out of this arbitration agreement (Sections 14.2 and 14.3 only) by emailing <a href="mailto:matthew@scriptandautomations.com">matthew@scriptandautomations.com</a> within 30 days of first accepting these Terms, with the subject line "Arbitration Opt-Out" and stating your account email and intent to opt out. Opting out does not affect any other provision of these Terms; if you opt out, disputes proceed in the state or federal courts located in Sheridan County, Wyoming, and you and QuickSDR consent to the personal jurisdiction of those courts.
            </p>

            <h2>15. Miscellaneous</h2>

            <ul>
                <li><strong>Entire agreement.</strong> These Terms, together with our Privacy Policy and any order forms or strategy-call agreements specific to a Tier 2/3/4 engagement, constitute the entire agreement between you and QuickSDR.</li>
                <li><strong>Assignment.</strong> You may not assign these Terms without our prior written consent. We may assign these Terms in connection with a merger, acquisition, or sale of substantially all of our assets.</li>
                <li><strong>Severability.</strong> If any provision is found unenforceable, the remaining provisions stay in effect.</li>
                <li><strong>No waiver.</strong> Failure to enforce any provision is not a waiver of that provision.</li>
                <li><strong>Force majeure.</strong> Neither party is liable for failures caused by events outside its reasonable control (e.g., infrastructure-provider outages, carrier-level disruptions, acts of god).</li>
            </ul>

            <h2>16. Contact</h2>

            <p>
                Questions about these Terms? Email <a href="mailto:matthew@scriptandautomations.com">matthew@scriptandautomations.com</a> or write to Quick Technologies, Inc., c/o the registered agent of record on file with the Wyoming Secretary of State, Sheridan County, Wyoming.
            </p>

            <div class="callout">
                <p><strong>Not legal advice.</strong> These Terms describe the agreement between you and QuickSDR in plain terms. They are not legal advice for your business. For your own contracts with the people you call, your clients, and your employees, consult an attorney licensed in your jurisdiction.</p>
            </div>
        </div>
    </article>

    {{-- Footer --}}
    @include('partials.public-footer')

    @fluxScripts
</body>
</html>
