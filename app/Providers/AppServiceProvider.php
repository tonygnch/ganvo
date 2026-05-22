<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
    }
}
