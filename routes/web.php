<?php

use App\Http\Controllers\Auth\AdminSignupController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnsureOrchestrator;
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
    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
        ->name('stripe.webhook')
        ->withoutMiddleware([VerifyCsrfToken::class]);
});
