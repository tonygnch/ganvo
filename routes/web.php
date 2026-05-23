<?php

use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\Marketing\SignupController;
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
    Route::get('/', function (\Illuminate\Http\Request $request) {
        // Coming-soon gate. When config('ganvo.coming_soon.enabled') is true,
        // the public homepage shows a "Launching soon" splash instead of the
        // full marketing site. Bypass via ?preview=<bypass_token> for
        // stakeholder previews without needing to log in. Scoped to this
        // route only — onboarding, admin, and storefronts remain reachable.
        $cs = (array) config('ganvo.coming_soon');
        if (! empty($cs['enabled'])) {
            $token = $cs['bypass_token'] ?? null;
            $bypass = is_string($token) && $token !== '' && $request->query('preview') === $token;
            if (! $bypass) {
                // Pull SA-editable strings up front (DB-backed, with locale
                // + i18n fallback) so the view doesn't need to know about
                // the SitePage model.
                $cs = \App\Models\SitePage::bulk(
                    \App\Services\SitePageSchemas::PAGE_COMING_SOON
                );

                return response()
                    ->view('marketing.coming-soon', compact('cs'))
                    // private — the page contains a locale-dependent body so
                    //   shared caches (CDNs, proxies) must not store one
                    //   user's localized HTML and serve it to others.
                    // Vary: Cookie — Chrome aggressively honors max-age; without
                    //   this, /lang/bg changes the locale cookie but Chrome
                    //   keeps serving the cached English page until the
                    //   max-age expires. Varying by Cookie keys the cache
                    //   entry by cookie value, so the new locale cookie is
                    //   treated as a different request → cache miss → refetch.
                    ->header('Cache-Control', 'private, max-age=300')
                    ->header('Vary', 'Cookie');
            }
        }

        // Plans are DB-driven and configurable in the SA panel; the marketing
        // pricing section reads from the same source as the wizard so any
        // edits there (new plan, discount, popular flag) show up on the
        // homepage without redeploy.
        $plans = \App\Models\Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // SA-editable text content (title, hero, section headings, CTA strip).
        // Falls back to i18n catalog for any field not overridden in DB.
        $cs = \App\Models\SitePage::bulk(
            \App\Services\SitePageSchemas::PAGE_MARKETING_HOME
        );

        return view('marketing.home', compact('plans', 'cs'));
    })->name('marketing.home');

    Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

    // Coming-soon waitlist signup. Reachable even when coming_soon mode is
    // off (the form still appears on the main marketing page in some
    // flows). Throttling + honeypot + dupe handling live in the controller.
    Route::post('/coming-soon/signup', [SignupController::class, 'store'])->name('marketing.signup');

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

    // The wizard step routes — auth-only. Each step has a show + save handler.
    Route::middleware('auth')->group(function () {
        Route::get('/onboarding', [WizardController::class, 'entry'])->name('onboarding.entry');

        Route::get('/onboarding/business', [WizardController::class, 'showBusiness'])->name('onboarding.business');
        Route::post('/onboarding/business', [WizardController::class, 'saveBusiness']);

        Route::get('/onboarding/plan', [WizardController::class, 'showPlan'])->name('onboarding.plan');
        Route::post('/onboarding/plan', [WizardController::class, 'savePlan']);
        // Stripe → back-to-wizard target when the merchant clicks "Pay now"
        // at the plan step instead of "Skip for now".
        Route::get('/onboarding/plan/checkout/success', [WizardController::class, 'planCheckoutSuccess'])
            ->name('onboarding.plan.checkout_success');

        Route::get('/onboarding/theme', [WizardController::class, 'showTheme'])->name('onboarding.theme');
        Route::post('/onboarding/theme', [WizardController::class, 'saveTheme']);

        // Live preview iframe target for the theme + customize steps. Accepts
        // ?primary, ?secondary, ?font query overrides for the customize live
        // preview that updates as the merchant tweaks form values.
        Route::get('/onboarding/theme/preview/{theme}', [WizardController::class, 'themePreview'])
            ->whereAlpha('theme')
            ->name('onboarding.theme.preview');

        Route::get('/onboarding/customize', [WizardController::class, 'showCustomize'])->name('onboarding.customize');
        Route::post('/onboarding/customize', [WizardController::class, 'saveCustomize']);
        // AJAX upload target for the customize step's live-preview logo.
        Route::post('/onboarding/customize/logo', [WizardController::class, 'uploadTempLogo'])->name('onboarding.customize.logo');

        Route::get('/onboarding/products', [WizardController::class, 'showProducts'])->name('onboarding.products');
        Route::post('/onboarding/products', [WizardController::class, 'saveProducts']);

        Route::get('/onboarding/launch', [WizardController::class, 'showLaunch'])->name('onboarding.launch');
        Route::post('/onboarding/launch', [WizardController::class, 'doLaunch']);

        Route::get('/onboarding/launched', [WizardController::class, 'showLaunched'])->name('onboarding.launched');
    });

    // Backwards-compat: legacy /store/register links should land on the wizard.
    Route::redirect('/store/register', '/onboarding/signup');

    // ---- Platform billing (Stripe Checkout + Customer Portal) ----
    // These routes are auth-only and operate on the authenticated merchant's
    // tenant. Filament's StoreAdmin Billing page redirects into them.
    Route::middleware('auth')->group(function () {
        Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('/billing/checkout/success', [BillingController::class, 'checkoutSuccess'])->name('billing.checkout.success');
        Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
        Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    });
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

    // Storefront-scoped lang + currency switchers. No ->name() here — the
    // closure is registered twice (once for the subdomain group, once for
    // the custom-domain catch-all), and a named duplicate breaks
    // `php artisan route:cache` ("name already assigned").
    Route::get('/lang/{locale}', [LanguageController::class, 'switch']);
    Route::get('/currency/{code}', [CurrencyController::class, 'switch'])
        ->whereAlpha('code');
};

// Subdomain routing: acme.ganvo.lvh.me
Route::domain('{tenantSlug}.' . $centralDomain)
    ->middleware([ResolveStorefrontTenant::class, SetDisplayCurrency::class])
    ->group($storefrontRoutes);

// Custom-domain (catch-all): any host that didn't match above falls through here.
// ResolveStorefrontTenant 404s if the host doesn't match a verified custom_domain.
Route::middleware([ResolveStorefrontTenant::class, SetDisplayCurrency::class])
    ->group($storefrontRoutes);
