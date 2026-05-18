<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

/**
 * Multi-step merchant onboarding wizard.
 *
 * Single controller hosts all step actions. Each step has a show/save pair;
 * save validates input, writes to the tenant or store, and (only if the
 * tenant is currently on this step or earlier) advances onboarding_step
 * forward — so re-saving an earlier step never rewinds a merchant who's
 * further along.
 *
 * The /onboarding entry point routes to the merchant's current step. Step
 * URLs themselves are open — once a merchant reaches step N, they can
 * navigate back to steps 1..N-1 via the progress pills to edit.
 */
class WizardController extends Controller
{
    private const BUSINESS_TYPES = [
        'retail'              => 'Retail (clothing, accessories)',
        'food_and_beverage'   => 'Food & beverage',
        'digital'             => 'Digital goods / software',
        'art_and_crafts'      => 'Art & crafts',
        'beauty_and_wellness' => 'Beauty & wellness',
        'electronics'         => 'Electronics',
        'home_and_garden'     => 'Home & garden',
        'services'            => 'Services',
        'other'               => 'Something else',
    ];

    private const PLANS = [
        'starter' => [
            'price_label' => 'Free',
            'tagline'     => 'Get your store online — no card required.',
            'features'    => [
                'Up to 25 products',
                '1 storefront theme',
                'Standard storefront on a *.ganvo.io subdomain',
                'Email support',
            ],
        ],
        'pro' => [
            'price_label' => '$29 / mo',
            'tagline'     => 'Grow without limits.',
            'features'    => [
                'Unlimited products',
                'All themes + customization',
                'Custom domain',
                'Multi-currency display',
                'Priority email support',
            ],
        ],
        'business' => [
            'price_label' => '$99 / mo',
            'tagline'     => 'For established stores ready to scale.',
            'features'    => [
                'Everything in Pro',
                'Advanced analytics',
                'Lower transaction fees',
                'Priority phone support',
                'Onboarding concierge',
            ],
        ],
    ];

    public function entry(): RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            return redirect('/onboarding/signup');
        }
        if ($tenant->isOnboarded()) {
            return redirect('/store');
        }
        return redirect('/onboarding/' . $tenant->onboarding_step);
    }

    // ---------------- Step 1: Business info ----------------

    public function showBusiness(): ViewContract
    {
        $tenant = $this->tenant();
        return view('onboarding.business', [
            'tenant'         => $tenant,
            'progressSteps' => $this->progressFor('business'),
            'businessTypes' => self::BUSINESS_TYPES,
        ]);
    }

    public function saveBusiness(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'business_type' => ['required', 'in:' . implode(',', array_keys(self::BUSINESS_TYPES))],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $tenant = $this->tenant();
        $tenant->update($data);
        $this->advanceIfOnOrBefore('business');

        return redirect('/onboarding/plan');
    }

    // ---------------- Step 2: Plan ----------------

    public function showPlan(): ViewContract
    {
        $tenant = $this->tenant();
        return view('onboarding.plan', [
            'tenant'         => $tenant,
            'progressSteps' => $this->progressFor('plan'),
            'plans'          => self::PLANS,
        ]);
    }

    public function savePlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subscription_plan' => ['required', 'in:' . implode(',', array_keys(self::PLANS))],
        ]);
        $this->tenant()->update($data);
        $this->advanceIfOnOrBefore('plan');
        return redirect('/onboarding/theme');
    }

    // ---------------- Step 3: Theme ----------------

    public function showTheme(): ViewContract
    {
        $tenant = $this->tenant();
        return view('onboarding.theme', [
            'tenant'         => $tenant,
            'progressSteps' => $this->progressFor('theme'),
            'themes'         => ThemeRegistry::all(),
        ]);
    }

    public function saveTheme(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'in:' . implode(',', ThemeRegistry::ids())],
        ]);
        $this->tenant()->store->update($data);
        $this->advanceIfOnOrBefore('theme');
        return redirect('/onboarding/customize');
    }

    /**
     * Live storefront preview, rendered chrome-less for embedding in an
     * iframe on the theme picker. Uses the merchant's tenant + an in-memory
     * store with the *previewed* theme overridden, plus any real products
     * they've added — or 3 synthetic ones if they're still empty.
     *
     * We have to manually do what SetDisplayCurrency / ResolveStorefrontTenant
     * would normally do, because this endpoint is on the central domain
     * (/onboarding/theme/preview/{theme}) — not a tenant subdomain.
     */
    public function themePreview(Request $request, string $theme): ViewContract
    {
        abort_unless(ThemeRegistry::exists($theme), 404);

        $tenant = $this->tenant();
        $store = clone $tenant->store; // unsaved override of theme
        $store->theme = $theme;

        $products = $tenant->products()->where('is_active', true)->take(6)->get();
        if ($products->isEmpty()) {
            $products = $this->sampleProducts();
        }

        // Bind the tenant so Cart::forCurrent() (referenced by the layout)
        // resolves cleanly to an empty cart for this merchant.
        app()->instance('current_tenant', $tenant);

        // Share what SetDisplayCurrency would normally share.
        $base = strtoupper($store->currency ?? 'USD');
        View::share('displayCurrency', $base);
        View::share('displayRate', 1.0);
        View::share('baseCurrency', $base);

        return view("themes.{$theme}.index", compact('tenant', 'store', 'products'));
    }

    // ---------------- helpers ----------------

    private function tenant(): Tenant
    {
        $tenant = Auth::user()?->tenant;
        abort_if(! $tenant, 404);
        return $tenant;
    }

    /**
     * Advance the wizard only if the tenant is at or before the named step,
     * so re-saving an earlier step from the progress strip doesn't rewind
     * someone further along.
     */
    private function advanceIfOnOrBefore(string $step): void
    {
        $tenant = $this->tenant();
        $current = array_search($tenant->onboarding_step, Tenant::ONBOARDING_STEPS, true);
        $target  = array_search($step, Tenant::ONBOARDING_STEPS, true);
        if ($current !== false && $target !== false && $current <= $target) {
            $tenant->onboarding_step = Tenant::ONBOARDING_STEPS[$target + 1];
            $tenant->save();
        }
    }

    /**
     * Build the labeled progress strip for the wizard layout. Each entry is
     * { label, state } where state is one of: done | current | pending.
     *
     * @return array<int, array{label: string, state: string}>
     */
    private function progressFor(string $currentStep): array
    {
        $tenant = $this->tenant();
        $stepLabels = [
            'business'  => __('site.onboarding.steps.business'),
            'plan'      => __('site.onboarding.steps.plan'),
            'theme'     => __('site.onboarding.steps.theme'),
            'customize' => __('site.onboarding.steps.customize'),
            'products'  => __('site.onboarding.steps.products'),
            'launch'    => __('site.onboarding.steps.launch'),
        ];

        $tenantStepIdx = array_search($tenant->onboarding_step, array_keys($stepLabels), true);
        if ($tenantStepIdx === false) {
            // 'done' — everything is complete.
            $tenantStepIdx = count($stepLabels);
        }

        $out = [];
        $i = 0;
        foreach ($stepLabels as $step => $label) {
            if ($step === $currentStep) {
                $state = 'current';
            } elseif ($i < $tenantStepIdx) {
                $state = 'done';
            } else {
                $state = 'pending';
            }
            $out[] = ['label' => $label, 'state' => $state];
            $i++;
        }
        return $out;
    }

    /** @return Collection<int, Product> */
    private function sampleProducts(): Collection
    {
        // Unsaved Product instances — only used to render the theme preview.
        $samples = [
            ['name' => 'Field Notebook', 'slug' => 's-1', 'price_cents' => 1499, 'stock_quantity' => 25, 'description' => 'A linen-bound notebook for everyday thinking.'],
            ['name' => 'Espresso Tin',   'slug' => 's-2', 'price_cents' => 2299, 'stock_quantity' => 12, 'description' => 'Single-origin beans, freshly roasted.'],
            ['name' => 'Linen Tote',     'slug' => 's-3', 'price_cents' => 3499, 'stock_quantity' => 30, 'description' => 'A simple, structured tote in oat linen.'],
            ['name' => 'Brass Bottle Opener', 'slug' => 's-4', 'price_cents' => 1999, 'stock_quantity' => 40, 'description' => 'Cast in solid brass, built to outlast you.'],
        ];
        return collect($samples)->map(fn ($s) => new Product(array_merge($s, [
            'currency'   => 'USD',
            'is_active'  => true,
            'image_path' => null,
        ])));
    }
}
