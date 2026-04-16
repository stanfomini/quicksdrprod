<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <flux:heading size="xl">New tenant</flux:heading>
                @if ($dryRun)
                    <flux:badge size="sm" color="amber" icon="beaker">Dry-run mode</flux:badge>
                @endif
            </div>
            <flux:text class="text-zinc-500">Hand-create a tenant for comps, demos, Tier 2/3 post-kickoff, or offline Stripe payers. Leaves payment wiring optional.</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.tenants.index')" wire:navigate>Back</flux:button>
    </div>

    @if ($dryRun)
        <flux:callout color="amber" icon="beaker">
            <flux:callout.heading>PROVISIONING_DRY_RUN is on</flux:callout.heading>
            <flux:callout.text>
                Tenants created now will simulate all 22 provisioning steps without touching Forge or Cloudflare. Set <code>PROVISIONING_DRY_RUN=false</code> in the environment once real credentials are in place.
            </flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:fieldset class="flex flex-col gap-4">
            <flux:legend>Company + admin</flux:legend>

            <flux:input wire:model="businessName" label="Business name" placeholder="Acme Solar" required />

            <flux:input wire:model="adminName" label="Admin name" placeholder="Jane Doe" required />

            <flux:input wire:model="adminEmail" type="email" label="Admin email" placeholder="jane@acmesolar.com" required />

            <flux:input wire:model="adminPhone" type="tel" label="Admin phone (optional)" placeholder="+15551234567" />

            <flux:input wire:model="roleTitle" label="Admin role (optional)" placeholder="Head of Sales" />

            <flux:input wire:model="companyWebsite" type="url" label="Company website (optional)" placeholder="https://acmesolar.com" />
        </flux:fieldset>

        <flux:fieldset class="flex flex-col gap-4">
            <flux:legend>Server + tier</flux:legend>

            <div>
                <flux:input wire:model.live.debounce.300ms="subdomain" label="Subdomain" placeholder="acme-solar" description="Becomes {subdomain}.quicksdr.com (ignored for Tier 4 consulting)" />
                @if ($subdomainStatus === 'invalid')
                    <flux:text size="sm" class="mt-1 text-red-500">Invalid — lowercase letters, digits, hyphens. Must start with a letter/digit. 2–40 chars.</flux:text>
                @elseif ($subdomainStatus === 'reserved')
                    <flux:text size="sm" class="mt-1 text-red-500">Reserved — pick another.</flux:text>
                @elseif ($subdomainStatus === 'taken')
                    <flux:text size="sm" class="mt-1 text-red-500">Already in use.</flux:text>
                @elseif ($subdomainStatus === 'available')
                    <flux:text size="sm" class="mt-1 text-emerald-500">Available.</flux:text>
                @endif
            </div>

            <flux:radio.group wire:model.live="tier" label="Tier">
                @foreach ($tiers as $t)
                    <flux:radio value="{{ $t['value'] }}">
                        <div class="flex flex-col">
                            <span class="font-medium">{{ $t['label'] }}</span>
                            <span class="text-xs text-zinc-500">{{ $t['price'] }}{{ $t['needs_server'] ? '' : ' — no server, consulting only' }}</span>
                        </div>
                    </flux:radio>
                @endforeach
            </flux:radio.group>

            <flux:separator />

            <flux:checkbox wire:model="skipPayment" label="Skip payment for now" description="Creates the tenant without charging. Generate a payment link from the detail page after." />

            @if ($tier !== 'tier4')
                <flux:checkbox wire:model="provisionImmediately" label="Provision server immediately" description="Dispatches the build pipeline right after create. Uncheck to stage and provision later." />
            @else
                <flux:callout color="blue" icon="information-circle">
                    <flux:callout.text>Tier 4 is hourly consulting — no server is provisioned. After creating, generate an hourly invoice from the tenant detail page.</flux:callout.text>
                </flux:callout>
            @endif
        </flux:fieldset>

        <flux:fieldset class="flex flex-col gap-4 lg:col-span-2">
            <flux:legend>Stripe link (optional)</flux:legend>
            <flux:text class="text-zinc-500">Paste IDs only if the customer already paid through a manually-shared Stripe link. Leave blank otherwise.</flux:text>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="stripeCustomerId" label="stripe_customer_id" placeholder="cus_..." />
                <flux:input wire:model="stripeSubscriptionId" label="stripe_subscription_id" placeholder="sub_..." />
            </div>
        </flux:fieldset>

        <div class="flex justify-end gap-3 lg:col-span-2">
            <flux:button type="button" variant="ghost" :href="route('admin.tenants.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="plus">Create tenant</flux:button>
        </div>
    </form>
</div>
