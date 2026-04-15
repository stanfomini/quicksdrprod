# Your login code

Hi {{ $name }},

Use this 6-digit code to finish signing in:

@component('mail::panel')
# **{{ $code }}**
@endcomponent

This code expires in **{{ $minutes }} minutes**.

If you didn't try to sign in, ignore this email — someone else may have entered your password by mistake. Consider changing it just to be safe.

Thanks,
QuickSDR
