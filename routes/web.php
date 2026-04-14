<?php

use App\Http\Controllers\Auth\AdminSignupController;
use App\Http\Controllers\SignupSuccessController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnsureOrchestrator;
use App\Livewire\Signup;
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
    Route::get('/signup/pending', fn () => view('signup.pending'))->name('signup.pending');
    Route::get('/signup/status/{tenant}', [SignupSuccessController::class, 'status'])->name('signup.status');

    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
        ->name('stripe.webhook')
        ->withoutMiddleware([VerifyCsrfToken::class]);
});
