@component('mail::message')
# Your QuickSDR trial is ending soon

Hi {{ $tenant->admin_name }},

Your free trial on **{{ $tenant->primaryHost() }}** ends @if($trialEndsAt){{ $trialEndsAt->diffForHumans() }} ({{ $trialEndsAt->toFormattedDayDateString() }})@else in a few days@endif. Your card on file will be charged automatically for your **{{ $tenant->tier?->label() }}** plan when the trial ends.

**No action needed if you're happy** — just keep dialing.

**Want to cancel?** Use the button below to head to your billing page. One click from there will take you to our secure Stripe portal where you can cancel before the trial ends.

@component('mail::button', ['url' => $billingUrl, 'color' => 'primary'])
Manage subscription
@endcomponent

Questions? Just reply to this email.

— Matthew
QuickSDR
@endcomponent
