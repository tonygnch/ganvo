<?php

use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Onboarding\AuthController as OnboardingAuthController;
use App\Http\Controllers\Onboarding\WizardController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\Auth\CustomerAuthController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CurrencyController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\StorefrontController;
use App\Http\Middleware\ResolveStorefrontTenant;
use App\Http\Middleware\SetDisplayCurrency;
use Illuminate\Support\Facades\Route;

$centralDomain = config('ganvo.central_domain');

Route::domain($centralDomain)->group(function () {
    Route::get('/', fn () => view('marketing.home'))->name('marketing.home');

    Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

    Route::post('/stop-impersonating', [ImpersonateController::class, 'stop'])
        ->middleware('auth')
        ->name('impersonate.stop');

    // ---- Merchant onboarding ----
    // Signup + login live here (not on tenant subdomains). The wizard owns
    // merchant signup end-to-end — Filament's ->registration() is disabled.
    Route::get('/onboarding/signup', [OnboardingAuthController::class, 'showSignup'])->name('onboarding.signup');
    Route::post('/onboarding/signup', [OnboardingAuthController::class, 'signup']);
    Route::get('/onboarding/login', [OnboardingAuthController::class, 'showLogin'])->name('onboarding.login');
    Route::post('/onboarding/login', [OnboardingAuthController::class, 'login']);
    Route::post('/onboarding/logout', [OnboardingAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('onboarding.logout');

    // The wizard entry point — routes the user to whatever step they're on.
    // Individual step routes will be added in subsequent slices.
    Route::middleware('auth')->group(function () {
        Route::get('/onboarding', [WizardController::class, 'entry'])->name('onboarding.entry');
    });

    // Backwards-compat: legacy /store/register links should land on the wizard.
    Route::redirect('/store/register', '/onboarding/signup');
});

// Shared storefront route definitions, reused for both subdomain and custom-domain matching.
$storefrontRoutes = function () {
    Route::get('/', [StorefrontController::class, 'index']);
    Route::get('/products/{slug}', [StorefrontController::class, 'product']);

    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/add/{slug}', [CartController::class, 'add']);
    Route::patch('/cart/{productId}', [CartController::class, 'update'])->whereNumber('productId');
    Route::delete('/cart/{productId}', [CartController::class, 'remove'])->whereNumber('productId');

    Route::get('/checkout', [CheckoutController::class, 'show']);
    Route::post('/checkout', [CheckoutController::class, 'process']);

    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);

    // Customer auth + account
    Route::get('/account/login', [CustomerAuthController::class, 'showLogin']);
    Route::post('/account/login', [CustomerAuthController::class, 'login']);
    Route::get('/account/register', [CustomerAuthController::class, 'showRegister']);
    Route::post('/account/register', [CustomerAuthController::class, 'register']);
    Route::post('/account/logout', [CustomerAuthController::class, 'logout']);
    Route::get('/account', [AccountController::class, 'show']);

    Route::get('/lang/{locale}', [LanguageController::class, 'switch']);
    Route::get('/currency/{code}', [CurrencyController::class, 'switch'])
        ->whereAlpha('code')
        ->name('currency.switch');
};

// Subdomain routing: acme.ganvo.lvh.me
Route::domain('{tenantSlug}.' . $centralDomain)
    ->middleware([ResolveStorefrontTenant::class, SetDisplayCurrency::class])
    ->group($storefrontRoutes);

// Custom-domain (catch-all): any host that didn't match above falls through here.
// ResolveStorefrontTenant 404s if the host doesn't match a verified custom_domain.
Route::middleware([ResolveStorefrontTenant::class, SetDisplayCurrency::class])
    ->group($storefrontRoutes);
