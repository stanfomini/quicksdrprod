<?php

use App\Http\Controllers\Auth\AdminSignupController;
use App\Http\Controllers\SignupSuccessController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TenantBillingPortalController;
use App\Http\Middleware\EnsureInternalUser;
use App\Http\Middleware\EnsureOrchestrator;
use App\Livewire\Admin\Tenants\Create as AdminTenantsCreate;
use App\Livewire\Admin\Tenants\Edit as AdminTenantsEdit;
use App\Livewire\Admin\Tenants\Index as AdminTenantsIndex;
use App\Livewire\Admin\Tenants\Show as AdminTenantsShow;
use App\Livewire\ContactSales;
use App\Livewire\Signup;
use App\Livewire\Tenant\Export as TenantExport;
use App\Models\Tenant;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
Route::view('/for-agencies', 'for-agencies')->name('for-agencies');
Route::view('/for-sales-teams', 'for-sales-teams')->name('for-sales-teams');

Route::get('/admin-signup/{token}', [AdminSignupController::class, 'show'])->name('admin-signup.show');
Route::post('/admin-signup/{token}', [AdminSignupController::class, 'store'])->name('admin-signup.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/claimstaker/dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// Tenant-side billing bounce. A tenant admin clicks "Manage billing" on
// their tenant app → HMAC-signed URL hits the orchestrator → Stripe
// Customer Portal session → redirect to Stripe's hosted page. No login
// required on quicksdr.com — the HMAC signature is the authentication.
// Route-binds by subdomain rather than ID — tenants know their own
// subdomain (it's in their APP_URL host), they don't know their tenant_id
// on the orchestrator side.
Route::get('/billing/portal/{tenant:subdomain}', TenantBillingPortalController::class)
    ->name('billing.portal')
    ->middleware(EnsureOrchestrator::class);

Route::middleware([EnsureOrchestrator::class])->group(function () {
    Route::get('/signup', Signup::class)->name('signup');
    Route::get('/signup/success', [SignupSuccessController::class, 'show'])->name('signup.success');
    Route::get('/signup/pending/{tenant?}', function (?Tenant $tenant = null) {
        return view('signup.pending', ['tenant' => $tenant]);
    })->name('signup.pending');
    Route::get('/signup/status/{tenant}', [SignupSuccessController::class, 'status'])->name('signup.status');

    Route::get('/contact-sales', ContactSales::class)->name('contact-sales');

    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
        ->name('stripe.webhook')
        ->withoutMiddleware([VerifyCsrfToken::class]);
});

// Tenant-side: /export page, shown only when the app is in export-only mode
// (config('app.export_only_until') is a future timestamp). Auth-gated.
Route::middleware(['auth'])->group(function () {
    Route::get('/export', TenantExport::class)->name('tenant.export');
});

// Email OTP step — second factor for authenticated users on production.
// Access to this route is granted by the RequireEmailOtp middleware
// (which whitelists /email-code to avoid a redirect loop).
Route::middleware(['auth'])->group(function () {
    Route::get('/email-code', App\Livewire\EmailOtp::class)->name('email-otp');
});

Route::middleware([EnsureOrchestrator::class, 'auth', EnsureInternalUser::class])
    ->prefix('admin/tenants')
    ->name('admin.tenants.')
    ->group(function () {
        Route::get('/', AdminTenantsIndex::class)->name('index');
        Route::get('/create', AdminTenantsCreate::class)->name('create');
        Route::get('/{tenant}', AdminTenantsShow::class)->name('show');
        Route::get('/{tenant}/edit', AdminTenantsEdit::class)->name('edit');
    });
