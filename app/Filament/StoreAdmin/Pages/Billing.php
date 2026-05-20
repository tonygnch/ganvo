<?php

namespace App\Filament\StoreAdmin\Pages;

use App\Models\Plan;
use App\Models\Tenant;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Merchant's billing dashboard — shows current plan + period + subscription
 * status, lets them switch plans (kicks off Stripe Checkout), and opens the
 * Stripe Customer Portal for billing management.
 *
 * The actual Stripe round-trips happen in {@see \App\Http\Controllers\Billing\BillingController}.
 * This page is the UI; the controller is the action.
 */
class Billing extends Page
{
    protected string $view = 'filament.store-admin.pages.billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Billing';

    protected static ?string $title = 'Billing';

    protected static ?int $navigationSort = 90;

    public function getViewData(): array
    {
        $tenant = $this->tenant();
        $plan = $tenant->plan();
        $subscription = $tenant->platformSubscription();

        return [
            'tenant' => $tenant,
            'currentPlan' => $plan,
            'currentPeriod' => $tenant->billing_period ?: Plan::PERIOD_MONTHLY,
            'subscription' => $subscription,
            'isSubscribed' => $tenant->platformSubscribed(),
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'periods' => Plan::PERIODS,
        ];
    }

    private function tenant(): Tenant
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user && $user->tenant_id, 403);
        $tenant = Tenant::find($user->tenant_id);
        abort_unless($tenant, 403);
        return $tenant;
    }
}
