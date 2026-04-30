@php
    $showAdminNav = config('app.mode') === 'orchestrator'
        && auth()->check()
        && (auth()->user()->type ?? null) === 'internal';
@endphp

@if ($showAdminNav)
    <flux:sidebar.group :heading="__('Admin')" expandable :expanded="true" class="grid">
        <flux:sidebar.item
            icon="building-office"
            :href="route('admin.tenants.index')"
            :current="request()->routeIs('admin.tenants.*')"
            wire:navigate
        >
            {{ __('Tenants') }}
        </flux:sidebar.item>
        <flux:sidebar.item
            icon="user-group"
            :href="route('admin.leads.index')"
            :current="request()->routeIs('admin.leads.*')"
            wire:navigate
        >
            {{ __('Leads') }}
        </flux:sidebar.item>
        <flux:sidebar.item
            icon="clipboard-document-list"
            :href="url('/internal/surveys')"
            target="_blank"
        >
            {{ __('Surveys') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
@endif
