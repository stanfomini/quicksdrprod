<div
    class="flex flex-col gap-6 p-6"
    x-data="{
        copy(text) {
            if (!text) return;
            navigator.clipboard.writeText(text);
        },
    }"
    @copy-to-clipboard.window="copy($event.detail.text)"
    @toast.window="window.dispatchEvent(new CustomEvent('flux-toast', { detail: $event.detail }))"
    x-init="
        if (typeof window.Echo !== 'undefined') {
            window.Echo.private('admin.tenants')
                .listen('.tenant.ready', (e) => { if (e.tenant_id === {{ $tenant->id }}) $wire.$refresh(); })
                .listen('.tenant.status-changed', (e) => { if (e.tenant_id === {{ $tenant->id }}) $wire.$refresh(); });
        }
    "
>
    <div class="flex items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $tenant->subdomain }}.quicksdr.com</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $tenant->tier->label() }}</flux:badge>
                <flux:badge size="sm" :color="$tenant->status->color()">{{ $tenant->status->label() }}</flux:badge>
                @if ($tenant->dry_run)
                    <flux:badge size="sm" color="amber" icon="beaker">dry-run</flux:badge>
                @endif
            </div>
            <flux:text class="text-zinc-500">{{ $tenant->business_name }}</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.tenants.index')" wire:navigate>Back</flux:button>
    </div>

    @php
        $stepLabels = [
            1 => 'Starting',
            2 => 'Validating subdomain + generating secrets',
            3 => 'Creating DigitalOcean server (8–12 min)',
            4 => 'Creating Cloudflare DNS records',
            5 => 'Creating primary site on Forge',
            6 => 'Installing Git repo',
            7 => 'Creating Postgres database',
            8 => 'Writing tenant .env file',
            9 => 'Requesting Let\'s Encrypt SSL',
            10 => 'Creating websocket site + SSL',
            11 => 'Writing deploy script + deploying',
            13 => 'Creating queue + Reverb daemons',
            14 => 'Installing Python (AI transcription)',
            15 => 'Setting up Reverb systemd service',
            16 => 'Enabling scheduler (cron)',
            17 => 'Seeding admin account',
            18 => 'Emailing admin credentials',
            19 => 'Posting PartnerStack conversion',
            20 => 'Broadcasting TenantReady',
            21 => 'Ready',
        ];
        $totalSteps = 22;
        $currentStep = max(0, (int) $tenant->status_step);
        $percent = min(100, (int) round(($currentStep / $totalSteps) * 100));
        $currentLabel = $stepLabels[$currentStep] ?? '—';
        $activeStatuses = [\App\Enums\TenantStatus::Pending, \App\Enums\TenantStatus::Provisioning];
    @endphp

    @if (in_array($tenant->status, $activeStatuses, true) || $tenant->status === \App\Enums\TenantStatus::Failed)
        <flux:card class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    @if ($tenant->status === \App\Enums\TenantStatus::Provisioning)
                        <svg class="h-5 w-5 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    @elseif ($tenant->status === \App\Enums\TenantStatus::Failed)
                        <flux:icon name="exclamation-triangle" class="h-5 w-5 text-red-500" />
                    @else
                        <flux:icon name="clock" class="h-5 w-5 text-zinc-500" />
                    @endif
                    <div>
                        <flux:heading size="lg">Step {{ $currentStep }} of {{ $totalSteps }} — {{ $currentLabel }}</flux:heading>
                        <flux:text class="text-zinc-500 text-sm">
                            @if ($tenant->status === \App\Enums\TenantStatus::Pending)
                                Queued — click "Provision now" above to start.
                            @elseif ($tenant->status === \App\Enums\TenantStatus::Failed)
                                Provisioning failed at step {{ $currentStep }}. See log below.
                            @else
                                Live updates via Reverb; manual refresh if needed.
                            @endif
                        </flux:text>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-semibold tabular-nums">{{ $percent }}%</div>
                    @if ($tenant->forge_server_id)
                        <a href="https://forge.laravel.com/servers/{{ $tenant->forge_server_id }}" target="_blank" class="text-xs text-blue-500 hover:underline">
                            Forge server #{{ $tenant->forge_server_id }} ↗
                        </a>
                    @endif
                </div>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                <div
                    class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 transition-all duration-500"
                    style="width: {{ $percent }}%"
                ></div>
            </div>
        </flux:card>
    @endif

    <div class="flex flex-wrap items-center gap-2">
        {{-- Pending + non-Tier-4: subscription payment link --}}
        @if ($tenant->status === \App\Enums\TenantStatus::Pending && $tenant->tier !== \App\Enums\TenantTier::Tier4 && ! $tenant->stripe_customer_id)
            <flux:button wire:click="generatePaymentLink" variant="primary" icon="credit-card">
                <span wire:loading.remove wire:target="generatePaymentLink">Generate payment link</span>
                <span wire:loading wire:target="generatePaymentLink">Generating…</span>
            </flux:button>
        @endif

        {{-- Tier 4: hourly invoice flow --}}
        @if ($tenant->tier === \App\Enums\TenantTier::Tier4)
            <flux:modal.trigger name="hourly-invoice">
                <flux:button variant="primary" icon="clock">Create hourly invoice</flux:button>
            </flux:modal.trigger>
        @endif

        {{-- Non-Tier-4 only: provision server --}}
        @if ($tenant->status === \App\Enums\TenantStatus::Pending && $tenant->tier !== \App\Enums\TenantTier::Tier4)
            <flux:modal.trigger name="provision-now">
                <flux:button variant="primary" icon="rocket-launch">Provision now</flux:button>
            </flux:modal.trigger>
        @endif

        {{-- Failed: retry from last step --}}
        @if ($tenant->status === \App\Enums\TenantStatus::Failed)
            <flux:button wire:click="retryProvisioning" variant="primary" icon="arrow-path">Retry provisioning</flux:button>
        @endif

        {{-- Destroyed / ExportOnly: rebuild (new server, fresh pipeline) --}}
        @if (in_array($tenant->status, [\App\Enums\TenantStatus::Destroyed, \App\Enums\TenantStatus::ExportOnly], true))
            <flux:modal.trigger name="rebuild-tenant">
                <flux:button variant="primary" icon="arrow-path">Rebuild server</flux:button>
            </flux:modal.trigger>
        @endif

        {{-- Ready: open the tenant app --}}
        @if ($tenant->status === \App\Enums\TenantStatus::Ready)
            <flux:button variant="ghost" icon="arrow-top-right-on-square" :href="$tenant->appUrl()" target="_blank">Open tenant</flux:button>
        @endif

        @if ($tenant->admin_signup_url)
            <flux:button wire:click="copySignupUrl" variant="ghost" icon="clipboard">Copy admin signup URL</flux:button>
        @endif

        {{-- Destroy: only when there's an active server to tear down --}}
        @if (in_array($tenant->status, [\App\Enums\TenantStatus::Ready, \App\Enums\TenantStatus::Failed, \App\Enums\TenantStatus::Provisioning], true))
            <flux:modal.trigger name="destroy-tenant">
                <flux:button variant="danger" icon="trash">Destroy server</flux:button>
            </flux:modal.trigger>
        @endif

        {{-- Delete record: only when no active server (pending with no server, or already destroyed) --}}
        @if (in_array($tenant->status, [\App\Enums\TenantStatus::Pending, \App\Enums\TenantStatus::Destroyed], true))
            <flux:modal.trigger name="delete-tenant">
                <flux:button variant="ghost" icon="trash" class="text-red-500 hover:text-red-400">Delete record</flux:button>
            </flux:modal.trigger>
        @endif
    </div>

    @if ($paymentLinkUrl)
        <flux:card class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <flux:icon name="credit-card" class="h-5 w-5 text-emerald-500" />
                <flux:heading size="lg">Payment link ready</flux:heading>
            </div>
            <flux:text class="text-zinc-500">
                @if ($tenant->tier === \App\Enums\TenantTier::Tier4)
                    {{ $tenant->tier->label() }} — {{ $tenant->status_detail['payment_link_hours'] ?? '?' }} hours @ $160/hr.
                @else
                    {{ $tenant->tier->label() }} — ${{ number_format($tenant->tier->setupFeeCents() / 100) }} setup + ${{ number_format($tenant->tier->monthlyPriceCents() / 100) }}/mo.
                @endif
                Single-use Stripe Checkout session; expires in 24h.
            </flux:text>
            <div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-3 font-mono text-xs text-zinc-300 break-all select-all">
                {{ $paymentLinkUrl }}
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button wire:click="copyPaymentLink" variant="ghost" icon="clipboard" size="sm">Copy URL</flux:button>
                <flux:button wire:click="emailPaymentLink" variant="primary" icon="envelope" size="sm">
                    <span wire:loading.remove wire:target="emailPaymentLink">Email to {{ $tenant->admin_email }}</span>
                    <span wire:loading wire:target="emailPaymentLink">Sending…</span>
                </flux:button>
            </div>
        </flux:card>
    @endif

    <flux:modal name="destroy-tenant" class="md:w-96">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg">Destroy {{ $tenant->subdomain }}?</flux:heading>
            <flux:text>This will dispatch the destroy job which tears down the Forge server, DNS records, and Stripe subscription. Final snapshot is taken first.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" wire:click="destroyServer">Destroy</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="provision-now" class="md:w-[32rem]">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg">Provision {{ $tenant->subdomain }} now?</flux:heading>
            @if (config('services.provisioning.dry_run'))
                <flux:callout color="amber" icon="beaker">
                    <flux:callout.heading>Dry-run mode is ON</flux:callout.heading>
                    <flux:callout.text>
                        PROVISIONING_DRY_RUN=true — this will simulate all 22 steps without touching Forge or Cloudflare. No real server is created. Flip the env var to false to do a real build.
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:callout color="red" icon="exclamation-triangle">
                    <flux:callout.heading>This will create a real DigitalOcean server</flux:callout.heading>
                    <flux:callout.text>
                        PROVISIONING_DRY_RUN is off. Clicking Provision will call Forge → DO → spin up a {{ $tenant->tier->serverSize() }} droplet (~$18–24/mo), create DNS records, request SSL, and deploy the tenant app. ~12–15 min end to end. Destroy via the Destroy button when done.
                    </flux:callout.text>
                </flux:callout>
            @endif
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="primary" icon="rocket-launch" wire:click="provisionNow">Provision</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="hourly-invoice" class="md:w-[28rem]">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg">Create hourly invoice</flux:heading>
            <flux:text class="text-zinc-500">Bills {{ $tenant->admin_email }} at $160/hr. Stripe Checkout — one-time payment, no subscription.</flux:text>
            <flux:input wire:model="hours" type="number" min="1" max="1000" label="Hours" required />
            <div class="rounded bg-zinc-800/50 p-3 text-sm">
                Total: <span class="font-mono font-medium text-emerald-400">${{ number_format($hours * 160) }}</span>
                <span class="text-zinc-500">({{ $hours }} × $160)</span>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="primary" icon="clock" wire:click="generateHourlyLink">Generate link</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="rebuild-tenant" class="md:w-[32rem]">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg">Rebuild {{ $tenant->subdomain }}?</flux:heading>
            @if (config('services.provisioning.dry_run'))
                <flux:callout color="amber" icon="beaker">
                    <flux:callout.heading>Dry-run mode is ON</flux:callout.heading>
                    <flux:callout.text>This will simulate a fresh 22-step build. No real server is created.</flux:callout.text>
                </flux:callout>
            @else
                <flux:callout color="blue" icon="information-circle">
                    <flux:callout.heading>Fresh server build</flux:callout.heading>
                    <flux:callout.text>All Forge artifacts (server, sites, daemons) will be created from scratch. Secrets are regenerated. The subdomain, Stripe link, and tenant record stay the same. Takes ~12-15 min.</flux:callout.text>
                </flux:callout>
            @endif
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="primary" icon="arrow-path" wire:click="rebuild">Rebuild</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delete-tenant" class="md:w-96">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg">Delete {{ $tenant->subdomain }}?</flux:heading>
            <flux:text>This permanently deletes the tenant record from the database. The subdomain becomes available again. This cannot be undone.</flux:text>
            @if ($tenant->stripe_customer_id)
                <flux:callout color="amber" icon="exclamation-triangle">
                    <flux:callout.text>This tenant has a Stripe customer ({{ $tenant->stripe_customer_id }}). Deleting the record won't cancel the subscription — do that in the Stripe dashboard first.</flux:callout.text>
                </flux:callout>
            @endif
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteTenant">Delete permanently</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Left: tenant info --}}
        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Tenant info</flux:heading>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-3">
                <dt class="text-zinc-500">Business</dt>
                <dd class="sm:col-span-2">{{ $tenant->business_name }}</dd>

                <dt class="text-zinc-500">Admin</dt>
                <dd class="sm:col-span-2">{{ $tenant->admin_name }}</dd>

                <dt class="text-zinc-500">Email</dt>
                <dd class="sm:col-span-2">{{ $tenant->admin_email }}</dd>

                <dt class="text-zinc-500">Phone</dt>
                <dd class="sm:col-span-2">{{ $tenant->admin_phone ?? '—' }}</dd>

                <dt class="text-zinc-500">Website</dt>
                <dd class="sm:col-span-2">
                    @if ($tenant->company_website)
                        <a href="{{ $tenant->company_website }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">{{ $tenant->company_website }}</a>
                    @else
                        —
                    @endif
                </dd>

                <dt class="text-zinc-500">Role</dt>
                <dd class="sm:col-span-2">{{ $tenant->role_title ?? '—' }}</dd>

                <dt class="text-zinc-500">Tier</dt>
                <dd class="sm:col-span-2">{{ $tenant->tier->label() }}</dd>

                <dt class="text-zinc-500">Stripe customer</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->stripe_customer_id ?? '—' }}</dd>

                <dt class="text-zinc-500">Stripe subscription</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->stripe_subscription_id ?? '—' }}</dd>

                <dt class="text-zinc-500">Forge server</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->forge_server_id ?? '—' }}</dd>

                <dt class="text-zinc-500">Forge site</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->forge_site_id ?? '—' }}</dd>

                <dt class="text-zinc-500">Server IP</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->server_ip ?? '—' }}</dd>

                <dt class="text-zinc-500">Created</dt>
                <dd class="sm:col-span-2">{{ $tenant->created_at?->toDayDateTimeString() }}</dd>

                <dt class="text-zinc-500">Provisioned</dt>
                <dd class="sm:col-span-2">{{ $tenant->provisioned_at?->toDayDateTimeString() ?? '—' }}</dd>
            </dl>
        </flux:card>

        {{-- Right: provisioning log --}}
        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Provisioning log</flux:heading>
            @if (count($this->log) === 0)
                <flux:text class="text-zinc-500">No log entries yet.</flux:text>
            @else
                <ol class="flex flex-col gap-3">
                    @foreach ($this->log as $entry)
                        @php
                            $isFailed = str_starts_with((string) ($entry['message'] ?? ''), 'failed');
                        @endphp
                        <li class="flex gap-3 border-l-2 pl-3 {{ $isFailed ? 'border-red-500' : 'border-zinc-300 dark:border-zinc-700' }}">
                            <div class="flex-shrink-0 text-xs font-mono text-zinc-500 w-16">#{{ $entry['step'] ?? '—' }}</div>
                            <div class="flex-1 text-sm">
                                <div class="{{ $isFailed ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">{{ $entry['message'] ?? '' }}</div>
                                <div class="text-xs text-zinc-500">
                                    @if (! empty($entry['at']))
                                        {{ \Illuminate\Support\Carbon::parse($entry['at'])->toDayDateTimeString() }}
                                    @endif
                                </div>
                                @if (! empty($entry['error']))
                                    <pre class="mt-1 text-xs text-red-500 whitespace-pre-wrap">{{ $entry['error'] }}</pre>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </flux:card>
    </div>

    {{-- Billing --}}
    <flux:card class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-3">
            <flux:heading size="lg">Billing</flux:heading>
            @if ($tenant->stripe_customer_id)
                <div class="flex gap-2">
                    <flux:button wire:click="openBillingPortal" variant="ghost" size="sm" icon="link">
                        <span wire:loading.remove wire:target="openBillingPortal">Copy customer portal link</span>
                        <span wire:loading wire:target="openBillingPortal">Generating…</span>
                    </flux:button>
                    <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square" :href="'https://dashboard.stripe.com/customers/' . $tenant->stripe_customer_id" target="_blank">Open in Stripe</flux:button>
                </div>
            @endif
        </div>

        @if (! $tenant->stripe_customer_id)
            <flux:text class="text-zinc-500">
                No Stripe customer yet. @if ($tenant->tier !== \App\Enums\TenantTier::Tier4)Generate a payment link above to charge this tenant.@else Use "Create hourly invoice" above to bill consulting hours.@endif
            </flux:text>
        @else
            @php($sub = $this->subscription)
            @if ($sub)
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded bg-zinc-800/40 p-3">
                        <div class="text-xs text-zinc-500">Status</div>
                        <div class="mt-1 flex items-center gap-2">
                            <flux:badge size="sm" :color="match ($sub['status']) { 'active' => 'green', 'trialing' => 'blue', 'past_due' => 'amber', 'canceled' => 'red', default => 'zinc' }">
                                {{ str_replace('_', ' ', $sub['status']) }}
                            </flux:badge>
                            @if (! empty($sub['cancel_at_period_end']))
                                <flux:badge size="sm" color="amber">cancelling</flux:badge>
                            @endif
                        </div>
                    </div>
                    <div class="rounded bg-zinc-800/40 p-3">
                        <div class="text-xs text-zinc-500">
                            @if ($sub['status'] === 'trialing' && $sub['trial_end'])
                                Trial ends
                            @else
                                Next invoice
                            @endif
                        </div>
                        <div class="mt-1 text-sm font-medium">
                            @php($when = $sub['trial_end'] ?? $sub['current_period_end'])
                            {{ $when ? \Illuminate\Support\Carbon::createFromTimestamp($when)->toDayDateTimeString() : '—' }}
                        </div>
                    </div>
                    <div class="rounded bg-zinc-800/40 p-3">
                        <div class="text-xs text-zinc-500">Amount</div>
                        <div class="mt-1 text-sm font-medium">
                            @if (! empty($sub['amount_cents']))
                                ${{ number_format($sub['amount_cents'] / 100, 2) }}/mo
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <flux:text class="text-zinc-500">Active Stripe customer, no subscription yet (e.g. Tier 4 consulting uses one-time invoices).</flux:text>
            @endif

            @php($invs = $this->invoices)
            @if (count($invs) > 0)
                <flux:separator />
                <flux:heading size="sm">Invoices</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Date</flux:table.column>
                        <flux:table.column>Number</flux:table.column>
                        <flux:table.column>Amount</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($invs as $inv)
                            <flux:table.row>
                                <flux:table.cell>{{ \Illuminate\Support\Carbon::createFromTimestamp($inv['created'])->format('M j, Y') }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs">{{ $inv['number'] ?? '—' }}</flux:table.cell>
                                <flux:table.cell>${{ number_format($inv['amount_paid'] / 100, 2) }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="match ($inv['status']) { 'paid' => 'green', 'open' => 'amber', 'uncollectible', 'void' => 'red', default => 'zinc' }">{{ $inv['status'] ?? '—' }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    @if (! empty($inv['hosted_invoice_url']))
                                        <a href="{{ $inv['hosted_invoice_url'] }}" target="_blank" class="text-xs text-blue-500 hover:underline">View ↗</a>
                                    @endif
                                    @if (! empty($inv['invoice_pdf']))
                                        <a href="{{ $inv['invoice_pdf'] }}" target="_blank" class="ml-2 text-xs text-blue-500 hover:underline">PDF</a>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:text class="text-zinc-500 text-sm">No invoices yet.</flux:text>
            @endif
        @endif
    </flux:card>

    {{-- Stats --}}
    <flux:card class="flex flex-col gap-4">
        <flux:heading size="lg">Recent activity (last 48 ingests)</flux:heading>
        @if ($this->recentStats->isEmpty())
            <flux:text class="text-zinc-500">No stats ingested yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Period</flux:table.column>
                    <flux:table.column>Agents</flux:table.column>
                    <flux:table.column>Calls</flux:table.column>
                    <flux:table.column>Talk time</flux:table.column>
                    <flux:table.column>Avg call</flux:table.column>
                    <flux:table.column>Ingested</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->recentStats as $stat)
                        <flux:table.row>
                            <flux:table.cell class="font-mono text-xs">{{ $stat->period_start?->format('Y-m-d H:i') }}</flux:table.cell>
                            <flux:table.cell>{{ $stat->agent_count }}</flux:table.cell>
                            <flux:table.cell>{{ $stat->call_count }}</flux:table.cell>
                            <flux:table.cell>{{ \Illuminate\Support\Carbon::createFromTimestamp(0)->addSeconds($stat->total_talk_seconds)->format('H:i:s') }}</flux:table.cell>
                            <flux:table.cell>{{ $stat->avg_call_duration_seconds }}s</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $stat->ingested_at?->diffForHumans() }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
