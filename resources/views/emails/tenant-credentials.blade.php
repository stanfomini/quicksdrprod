# Your QuickSDR is ready, {{ $tenant->admin_name }}

Welcome aboard. Your dedicated QuickSDR instance is now live.

## Your URLs

- **App:** [{{ $appUrl }}]({{ $appUrl }})
- **WebSocket:** `{{ $wsUrl }}` (no need to visit — this is how your agents stay connected in real-time)

## First login

Click the button below to set your password and finish setting up your account. This link is single-use and expires in 72 hours.

@component('mail::button', ['url' => $loginUrl])
Set up your account
@endcomponent

## What's next

After you log in, head to **Settings** in the sidebar to add:

- **Twilio credentials** — for placing calls and receiving SMS. If you don't have a Twilio A2P registration yet, no worries: you can reach out and we'll help you get set up.
- **AI provider key** — xAI (Grok), Anthropic, OpenAI, or any of the supported providers. Needed for call transcription and lead scoring.

Your first **admin signup link** above gets you in with full access; you can invite your team (SDRs, managers) from the Agents page once you're in.

## Support

If anything looks off or you hit a wall:

- Reply to this email
- Or call us at **(757) 343-3032**

Thanks,
QuickSDR
