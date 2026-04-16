@component('mail::message')
# Payment failed — action needed

Hi {{ $tenant->admin_name }},

We weren't able to charge the card on file for your QuickSDR subscription on **{{ $tenant->primaryHost() }}**.@if($attemptCount > 1) This is attempt {{ $attemptCount }}.@endif

@if($nextAttemptAt)
Stripe will automatically retry on **{{ $nextAttemptAt->toFormattedDayDateString() }}**. If that attempt also fails, your access may be suspended.
@else
Stripe has exhausted its automatic retries. **Your subscription will be canceled soon unless you update your payment method.**
@endif

**Please update your payment method** to keep your dialer running:

@component('mail::button', ['url' => $billingUrl, 'color' => 'error'])
Update payment method
@endcomponent

The link above takes you to your billing page. Click "Open Stripe portal" from there to securely update your card.

Questions? Reply to this email or call **(757) 343-3032**.

— Matthew
QuickSDR
@endcomponent
