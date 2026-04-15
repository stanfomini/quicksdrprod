<?php

use App\Http\Controllers\Auth\AdminSignupController;
use App\Http\Controllers\SignupSuccessController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnsureInternalUser;
use App\Http\Middleware\EnsureOrchestrator;
use App\Livewire\Admin\Tenants\Index as AdminTenantsIndex;
use App\Livewire\Admin\Tenants\Show as AdminTenantsShow;
use App\Livewire\ContactSales;
use App\Livewire\Signup;
use App\Livewire\Tenant\Export as TenantExport;
use App\Models\Tenant;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::get('/admin-signup/{token}', [AdminSignupController::class, 'show'])->name('admin-signup.show');
Route::post('/admin-signup/{token}', [AdminSignupController::class, 'store'])->name('admin-signup.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/claimstaker/dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

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

Route::middleware([EnsureOrchestrator::class, 'auth', EnsureInternalUser::class])
    ->prefix('admin/tenants')
    ->name('admin.tenants.')
    ->group(function () {
        Route::get('/', AdminTenantsIndex::class)->name('index');
        Route::get('/{tenant}', AdminTenantsShow::class)->name('show');
    });
