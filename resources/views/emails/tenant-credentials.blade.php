@component('mail::message')
# Welcome to QuickSDR, {{ $tenant->admin_name }}

Your dedicated dialer is live at **[{{ $appUrl }}]({{ $appUrl }})**.

**Next step:** click the button below to set your password. Takes about 30 seconds. Your admin account is already created — this link just lets you choose a password.

@component('mail::button', ['url' => $loginUrl, 'color' => 'success'])
Set my password
@endcomponent

**Button not working?** Copy and paste this link into your browser:

{{ $loginUrl }}

This link is valid for 60 minutes. If it expires, go to **[{{ $appUrl }}/forgot-password]({{ $appUrl }}/forgot-password)** and request a new one — your account stays ready.

---

## Once you're in

Head to **Settings** in the sidebar to add:

- **Twilio credentials** — for placing calls and receiving SMS. No A2P registration yet? Reply to this email and we'll help you get set up.
- **AI provider key** — xAI, Anthropic, or OpenAI, for call transcription and lead scoring.

Then invite your team (SDRs, managers) from the **Agents** page and start dialing.

---

**Questions or stuck?** Reply to this email or call **(757) 343-3032** — we'll get you unblocked fast.

— Matthew
QuickSDR
@endcomponent
