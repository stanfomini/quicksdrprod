<?php

namespace App\Livewire;

use App\Mail\LoginCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Enter your login code — QuickSDR')]
class EmailOtp extends Component
{
    public string $code = '';

    public ?string $flash = null;

    public ?int $resendCooldown = null;

    /**
     * Cache key + TTL for the active code.
     */
    private const CACHE_TTL_SECONDS = 300;  // 5 minutes

    private const MAX_VERIFY_ATTEMPTS = 5;

    public function mount(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        // Send a code on first arrival if one isn't already cached.
        if (! Cache::has($this->cacheKey())) {
            $this->issueCode(silent: true);
        }
    }

    public function resend(): void
    {
        $key = 'email-otp-resend:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 1)) {
            $this->resendCooldown = RateLimiter::availableIn($key);
            $this->flash = "Please wait {$this->resendCooldown}s before requesting another code.";

            return;
        }

        RateLimiter::hit($key, decaySeconds: 30);
        $this->issueCode();
    }

    public function verify(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $attemptsKey = 'email-otp-verify:'.auth()->id();

        if (RateLimiter::tooManyAttempts($attemptsKey, maxAttempts: self::MAX_VERIFY_ATTEMPTS)) {
            $this->flash = 'Too many incorrect attempts. Please request a new code.';
            Cache::forget($this->cacheKey());

            return;
        }

        $expected = Cache::get($this->cacheKey());

        if (! $expected || ! hash_equals((string) $expected, $this->code)) {
            RateLimiter::hit($attemptsKey, decaySeconds: 600);
            $this->flash = 'That code is incorrect or has expired.';
            $this->code = '';

            return;
        }

        // Success: mark verified for 24h, clear cache, forward to intended URL.
        Cache::forget($this->cacheKey());
        RateLimiter::clear($attemptsKey);

        session()->put('email_otp_verified_at', now());

        $intended = session()->pull('email_otp_intended', '/claimstaker/dashboard');
        $this->redirect($intended);
    }

    private function issueCode(bool $silent = false): void
    {
        $code = (string) random_int(100000, 999999);
        Cache::put($this->cacheKey(), $code, self::CACHE_TTL_SECONDS);

        Mail::to(auth()->user()->email)->queue(new LoginCode(
            user: auth()->user(),
            code: $code,
            expiresInMinutes: (int) (self::CACHE_TTL_SECONDS / 60),
        ));

        if (! $silent) {
            $this->flash = 'A new code has been sent to '.Str::mask(auth()->user()->email, '*', 2, -12).'.';
        }
    }

    private function cacheKey(): string
    {
        return 'email-otp:'.auth()->id();
    }

    public function render()
    {
        return view('livewire.email-otp', [
            'maskedEmail' => auth()->user() ? Str::mask(auth()->user()->email, '*', 2, -12) : null,
        ]);
    }
}
