<div class="flex flex-col gap-6 p-6 max-w-2xl">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Edit lead</flux:heading>
            <flux:text class="text-zinc-500">{{ $lead->email ?: $lead->phone ?: 'Lead #'.$lead->id }}</flux:text>
        </div>
        <flux:button :href="route('admin.leads.show', $lead)" wire:navigate variant="ghost" icon="arrow-left">Cancel</flux:button>
    </div>

    <form wire:submit="save" class="flex flex-col gap-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
        <flux:input wire:model="name" label="Name" />
        <flux:input wire:model="email" type="email" label="Email" />
        <flux:input wire:model="phone" type="tel" label="Phone" description="Will be normalized to +E.164 format on save." />
        <flux:input wire:model="company" label="Company" />
        <flux:input wire:model="source" label="Source" description="Free-text — e.g. survey:sales-discovery, manual, referral." />

        <div class="flex justify-end gap-2 pt-2">
            <flux:button :href="route('admin.leads.show', $lead)" wire:navigate variant="ghost">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
