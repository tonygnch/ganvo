<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use App\Services\RoleMatrix;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tell Cashier the Billable model is Tenant (NOT the default
        // App\Models\User). Without this, webhook handlers do
        // User::where('stripe_id', ...)->first() and return null — so
        // events like customer.subscription.created return 200 but
        // silently skip syncing, leaving the local `subscriptions` table
        // empty even though Stripe has the subscription.
        Cashier::useCustomerModel(Tenant::class);

        // Force https:// in generated URLs when behind a TLS-terminating
        // proxy (Caddy in prod). Without this, url() / route() / asset()
        // can leak http:// links — mixed-content warnings + broken assets
        // on https pages. Opt-in via APP_FORCE_HTTPS=true in .env.
        if (env('APP_FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // @money($cents) — format a base-currency amount using the current
        // request's display currency + FX rate (shared by SetDisplayCurrency).
        // Falls back to base currency at rate 1.0 if not set (e.g. admin views).
        Blade::directive('money', function (string $expression) {
            return "<?php echo \App\Services\Money::display(
                {$expression},
                \$displayRate ?? 1.0,
                \$displayCurrency ?? (isset(\$store) ? \$store->currency : 'EUR')
            ); ?>";
        });

        // super_admin god-mode. Runs before any other gate / policy check,
        // so super admins implicitly pass every $user->can() lookup —
        // including permissions that get added to the catalog later. Other
        // roles fall through to the normal permission check (which Spatie
        // handles via the HasRoles trait on User).
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole(RoleMatrix::SUPER_ADMIN)) {
                return true;
            }
            return null; // null = "I don't decide" — let Spatie answer.
        });
    }
}
