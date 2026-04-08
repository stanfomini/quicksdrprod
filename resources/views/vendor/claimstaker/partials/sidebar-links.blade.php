{{-- Local override of claimstaker::partials.sidebar-links --}}
{{-- The Claimstaker group is always expanded by default. --}}
@php
    $claimstakerAgent = \Stanfomini\QuickClaimstaker\Models\Agent::forCurrentUser();
@endphp

@if ($claimstakerAgent)
    <flux:sidebar.group
        :heading="__('Claimstaker')"
        expandable
        :expanded="true"
        class="grid"
    >
        <flux:sidebar.item
            icon="phone-arrow-up-right"
            :href="route('claimstaker.dashboard')"
            :current="request()->routeIs('claimstaker.dashboard')"
            wire:navigate
        >
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        @if ($claimstakerAgent->hasPermission('dialer.use'))
            <flux:sidebar.item
                icon="phone"
                :href="route('claimstaker.dialer')"
                :current="request()->routeIs('claimstaker.dialer')"
                wire:navigate
            >
                {{ __('Dialer') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('reports.view'))
            <flux:sidebar.item
                icon="chart-bar-square"
                :href="route('claimstaker.reports')"
                :current="request()->routeIs('claimstaker.reports')"
                wire:navigate
            >
                {{ __('Reports') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('contacts.view'))
            <flux:sidebar.item
                icon="users"
                :href="route('claimstaker.contacts')"
                :current="request()->routeIs('claimstaker.contacts')"
                wire:navigate
            >
                {{ __('Contacts') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('campaigns.view'))
            <flux:sidebar.item
                icon="megaphone"
                :href="route('claimstaker.campaigns')"
                :current="request()->routeIs('claimstaker.campaigns*')"
                wire:navigate
            >
                {{ __('Campaigns') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('sequences.view'))
            <flux:sidebar.item
                icon="arrow-path"
                :href="route('claimstaker.sequences')"
                :current="request()->routeIs('claimstaker.sequences*')"
                wire:navigate
            >
                {{ __('Sequences') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('companies.view'))
            <flux:sidebar.item
                icon="building-office-2"
                :href="route('claimstaker.companies')"
                :current="request()->routeIs('claimstaker.companies*')"
                wire:navigate
            >
                {{ __('Companies') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('settings.twilio'))
            <flux:sidebar.item
                icon="device-phone-mobile"
                :href="route('claimstaker.phone-numbers')"
                :current="request()->routeIs('claimstaker.phone-numbers')"
                wire:navigate
            >
                {{ __('Phone Numbers') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('rvm.view'))
            <flux:sidebar.item
                icon="speaker-wave"
                :href="route('claimstaker.rvm')"
                :current="request()->routeIs('claimstaker.rvm')"
                wire:navigate
            >
                {{ __('Voicemail Drops') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('recordings.intelligence'))
            <flux:sidebar.item
                icon="cpu-chip"
                :href="route('claimstaker.call-intelligence')"
                :current="request()->routeIs('claimstaker.call-intelligence')"
                wire:navigate
            >
                {{ __('AI Intelligence') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('recordings.listen'))
            <flux:sidebar.item
                icon="clock"
                :href="route('claimstaker.call-history')"
                :current="request()->routeIs('claimstaker.call-history')"
                wire:navigate
            >
                {{ __('Call History') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('agents.view'))
            <flux:sidebar.item
                icon="user-group"
                :href="route('claimstaker.agents')"
                :current="request()->routeIs('claimstaker.agents')"
                wire:navigate
            >
                {{ __('Agents & Roles') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('settings.twilio'))
            <flux:sidebar.item
                icon="cog-6-tooth"
                :href="route('claimstaker.settings')"
                :current="request()->routeIs('claimstaker.settings')"
                wire:navigate
            >
                {{ __('Settings') }}
            </flux:sidebar.item>
        @endif

        @if ($claimstakerAgent->hasPermission('settings.integrations'))
            <flux:sidebar.item
                icon="arrow-path-rounded-square"
                :href="route('claimstaker.settings.integrations')"
                :current="request()->routeIs('claimstaker.settings.integrations')"
                wire:navigate
            >
                {{ __('Integrations') }}
            </flux:sidebar.item>
        @endif
    </flux:sidebar.group>
@endif
