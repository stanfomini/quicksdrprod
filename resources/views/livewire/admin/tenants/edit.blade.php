<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <flux:heading size="xl">Edit {{ $tenant->subdomain }}.quicksdr.com</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $tenant->tier->label() }}</flux:badge>
                <flux:badge size="sm" :color="$tenant->status->color()">{{ $tenant->status->label() }}</flux:badge>
            </div>
            <flux:text class="text-zinc-500">Update contact details for this tenant. Subdomain and tier are locked after creation.</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.tenants.show', $tenant)" wire:navigate>Cancel</flux:button>
    </div>

    @if ($tenant->forge_site_id)
        <flux:callout color="amber" icon="information-circle">
            <flux:callout.heading>Email changes only update the orchestrator record</flux:callout.heading>
            <flux:callout.text>
                To push an admin-email change to the tenant's own server (so the tenant can log in with the new address),
                save here first, then use the "Update on tenant server" button on the detail page.
                Stripe customer email is synced automatically.
            </flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:fieldset class="flex flex-col gap-4">
            <flux:legend>Company + admin</flux:legend>

            <flux:input wire:model="businessName" label="Business name" required />

            <flux:input wire:model="adminName" label="Admin name" required />

            <flux:input wire:model="adminEmail" type="email" label="Admin email" required />

            <flux:input wire:model="adminPhone" type="tel" label="Admin phone (optional)" />

            <flux:input wire:model="roleTitle" label="Admin role (optional)" />

            <flux:input wire:model="companyWebsite" type="url" label="Company website (optional)" />
        </flux:fieldset>

        <flux:fieldset class="flex flex-col gap-4">
            <flux:legend>Locked fields</flux:legend>
            <flux:text class="text-zinc-500 text-sm">These fields can't be edited here — changing them would cascade to DNS, billing, or provisioning artifacts.</flux:text>

            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-3">
                <dt class="text-zinc-500">Subdomain</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->subdomain }}</dd>

                <dt class="text-zinc-500">Tier</dt>
                <dd class="sm:col-span-2">{{ $tenant->tier->label() }}</dd>

                <dt class="text-zinc-500">Status</dt>
                <dd class="sm:col-span-2">{{ $tenant->status->label() }}</dd>

                <dt class="text-zinc-500">Stripe customer</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->stripe_customer_id ?? '—' }}</dd>

                <dt class="text-zinc-500">Forge server</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->forge_server_id ?? '—' }}</dd>

                <dt class="text-zinc-500">Forge site</dt>
                <dd class="sm:col-span-2 font-mono text-xs">{{ $tenant->forge_site_id ?? '—' }}</dd>
            </dl>
        </flux:fieldset>

        <div class="flex justify-end gap-3 lg:col-span-2">
            <flux:button type="button" variant="ghost" :href="route('admin.tenants.show', $tenant)" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
        </div>
    </form>
</div>
