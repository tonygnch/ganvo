<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\Money;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
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

    // Plans now live in the `plans` table and are managed by super admins —
    // App\Filament\SuperAdmin\Resources\Plans. The wizard fetches them at
    // runtime so changes (pricing, discounts, popular flag, new plans) take
    // effect without redeploy.

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
            'store'          => $tenant->store,
            'progressSteps' => $this->progressFor('business'),
            'businessTypes' => self::BUSINESS_TYPES,
            'currencies'    => Money::options(),
        ]);
    }

    public function saveBusiness(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'business_type' => ['required', 'in:' . implode(',', array_keys(self::BUSINESS_TYPES))],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'currency'      => ['required', 'in:' . implode(',', array_keys(Money::SUPPORTED))],
        ]);

        $tenant = $this->tenant();
        $tenant->update([
            'name'          => $data['name'],
            'business_type' => $data['business_type'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? null,
        ]);
        // Currency is on the store, not the tenant — make sure the merchant's
        // first product later gets priced in the right base.
        $tenant->store->update(['currency' => strtoupper($data['currency'])]);

        $this->advanceIfOnOrBefore('business');

        return redirect($this->nextStepUrl('business'));
    }

    // ---------------- Step 2: Plan ----------------

    public function showPlan(): ViewContract
    {
        $tenant = $this->tenant();
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return view('onboarding.plan', [
            'tenant'        => $tenant,
            'progressSteps' => $this->progressFor('plan'),
            'plans'         => $plans,
        ]);
    }

    public function savePlan(Request $request): RedirectResponse
    {
        // Build the allowed slug list dynamically so newly added plans work
        // without code changes.
        $allowedSlugs = Plan::query()->where('is_active', true)->pluck('slug')->all();
        $data = $request->validate([
            'subscription_plan' => ['required', 'in:' . implode(',', $allowedSlugs)],
            'billing_period'    => ['required', 'in:' . implode(',', Plan::PERIODS)],
        ]);
        $this->tenant()->update($data);
        $this->advanceIfOnOrBefore('plan');
        return redirect($this->nextStepUrl('plan'));
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
        return redirect($this->nextStepUrl('theme'));
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

        // Query overrides — used by the customize step's live preview so the
        // iframe reflects the form state without a save round-trip.
        $primary   = $request->query('primary');
        $secondary = $request->query('secondary');
        $font      = $request->query('font');
        $logo      = $request->query('logo');
        if (is_string($primary) && preg_match('/^#?[0-9a-fA-F]{6}$/', $primary)) {
            $store->primary_color = str_starts_with($primary, '#') ? $primary : '#' . $primary;
        }
        if (is_string($secondary) && preg_match('/^#?[0-9a-fA-F]{6}$/', $secondary)) {
            $store->secondary_color = str_starts_with($secondary, '#') ? $secondary : '#' . $secondary;
        }
        if (is_string($font) && $font !== '') {
            $store->font_family = $font;
        }
        if (is_string($logo) && str_starts_with($logo, 'logos/onboarding-temp/')) {
            // Path-only — the iframe view will run it through Storage::url().
            // Restrict to the temp dir to prevent arbitrary cross-tenant
            // logo previewing.
            $store->logo_path = $logo;
        }

        $products = $tenant->products()->where('is_active', true)->take(6)->get();
        if ($products->isEmpty()) {
            $products = $this->sampleProducts();
        }

        // Bind the tenant so Cart::forCurrent() (referenced by the layout)
        // resolves cleanly to an empty cart for this merchant.
        app()->instance('current_tenant', $tenant);

        // Share what SetDisplayCurrency would normally share.
        $base = strtoupper($store->currency ?? 'EUR');
        View::share('displayCurrency', $base);
        View::share('displayRate', 1.0);
        View::share('baseCurrency', $base);

        return view("themes.{$theme}.index", compact('tenant', 'store', 'products'));
    }

    // ---------------- Step 4: Customize ----------------

    public function showCustomize(): ViewContract
    {
        $tenant = $this->tenant();
        return view('onboarding.customize', [
            'tenant'         => $tenant,
            'store'          => $tenant->store,
            'progressSteps' => $this->progressFor('customize'),
            'fonts'          => $this->fonts(),
        ]);
    }

    public function saveCustomize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary_color'   => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_family'     => ['required', 'string', 'max:60'],
            'logo'            => ['nullable', 'image', 'max:2048'],
        ]);

        $store = $this->tenant()->store;
        $updates = [
            'primary_color'   => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'font_family'     => $data['font_family'],
        ];
        if ($request->hasFile('logo')) {
            $updates['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        $store->update($updates);
        $this->advanceIfOnOrBefore('customize');

        return redirect($this->nextStepUrl('customize'));
    }

    /**
     * AJAX endpoint used by the customize step's live preview to reflect a
     * newly-chosen logo *before* the merchant saves the form.
     *
     * Saves the upload to a temp directory keyed by tenant. The final form
     * submit re-uploads the file from the form's `<input type="file">` to the
     * canonical `logos/` directory; this temp file is just for previewing.
     */
    public function uploadTempLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);
        $tenant = $this->tenant();
        $dir = 'logos/onboarding-temp/' . $tenant->id;
        $path = $request->file('logo')->store($dir, 'public');
        return response()->json([
            'path' => $path,
            'url'  => $path, // The themePreview endpoint expects the raw path.
        ]);
    }

    // ---------------- Step 5: Products ----------------

    public function showProducts(): ViewContract
    {
        $tenant = $this->tenant();
        return view('onboarding.products', [
            'tenant'         => $tenant,
            'store'          => $tenant->store,
            'products'       => $tenant->products()->latest()->get(),
            'progressSteps' => $this->progressFor('products'),
        ]);
    }

    public function saveProducts(Request $request): RedirectResponse
    {
        $action = $request->input('action', 'continue'); // continue | another | skip

        if ($action !== 'skip') {
            $data = $request->validate([
                'name'        => ['required', 'string', 'max:120'],
                'price'       => ['required', 'numeric', 'min:0'],
                'description' => ['nullable', 'string', 'max:2000'],
                'image'       => ['nullable', 'image', 'max:2048'],
            ]);

            $tenant = $this->tenant();
            $imagePath = $request->hasFile('image')
                ? $request->file('image')->store('products', 'public')
                : null;

            Product::create([
                'tenant_id'      => $tenant->id,
                'name'           => $data['name'],
                'slug'           => $this->uniqueProductSlug($tenant, $data['name']),
                'description'    => $data['description'] ?? null,
                'price_cents'    => (int) round(((float) $data['price']) * 100),
                'currency'       => $tenant->store->currency ?? 'EUR',
                'stock_quantity' => 100,
                'is_active'      => true,
                'image_path'     => $imagePath,
            ]);

            if ($action === 'another') {
                // Stay on the products step, accumulating items.
                return redirect('/onboarding/products')
                    ->with('flash', __('site.onboarding.products.added'));
            }
        }

        $this->advanceIfOnOrBefore('products');
        return redirect($this->nextStepUrl('products'));
    }

    // ---------------- Step 6: Launch ----------------

    public function showLaunch(): ViewContract
    {
        $tenant = $this->tenant();
        return view('onboarding.launch', [
            'tenant'         => $tenant,
            'store'          => $tenant->store,
            'progressSteps' => $this->progressFor('launch'),
            'productCount'   => $tenant->products()->count(),
            'storefrontUrl'  => $this->storefrontUrlFor($tenant),
        ]);
    }

    public function doLaunch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Optional — the merchant can launch on the *.ganvo subdomain
            // and add a custom domain later from Store Settings. Same regex
            // as the StoreSettings form so the rules stay consistent.
            'custom_domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9.\-]+[a-z0-9]$/'],
        ]);

        $tenant = $this->tenant();
        $tenant->update([
            'status'          => Tenant::STATUS_ACTIVE,
            'onboarding_step' => 'done',
            'onboarded_at'    => now(),
        ]);
        $storeUpdates = ['is_live' => true];
        $newDomain = $data['custom_domain'] ?? null;
        if ($newDomain) {
            $storeUpdates['custom_domain'] = $newDomain;
            // Ensure verification starts from scratch — the merchant will
            // verify DNS from Store Settings after launch.
            $storeUpdates['custom_domain_verified_at'] = null;
            $storeUpdates['custom_domain_verification_token'] = null;
        }
        $tenant->store->update($storeUpdates);
        if ($newDomain) {
            $tenant->store->ensureVerificationToken();
        }

        return redirect('/onboarding/launched');
    }

    // Celebration / "you're live" page. Stays accessible even after step=done
    // so the merchant can revisit it from the URL — but the wizard entry
    // point at /onboarding sends them to /store from then on.
    public function showLaunched(): ViewContract|RedirectResponse
    {
        $tenant = $this->tenant();
        if (! $tenant->isOnboarded()) {
            return redirect('/onboarding');
        }
        return view('onboarding.launched', [
            'tenant'        => $tenant,
            'storefrontUrl' => $this->storefrontUrlFor($tenant),
        ]);
    }

    private function uniqueProductSlug(Tenant $tenant, string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $attempts = 0;
        while (Product::where('tenant_id', $tenant->id)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::lower(Str::random(4));
            if (++$attempts > 10) {
                $slug = 'product-' . Str::lower(Str::random(8));
                break;
            }
        }
        return $slug;
    }

    /**
     * Build the public-facing storefront URL for the tenant subdomain.
     * Derived from APP_URL so it works in both local dev (with the :8000
     * port) and production.
     */
    private function storefrontUrlFor(Tenant $tenant): string
    {
        $parsed = parse_url((string) config('app.url')) ?: [];
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? config('ganvo.central_domain', 'ganvo.bg');
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        return "{$scheme}://{$tenant->slug}.{$host}{$port}";
    }

    private function fonts(): array
    {
        return [
            'Inter'              => 'Inter — modern sans',
            'Roboto'             => 'Roboto — clean sans',
            'Lato'               => 'Lato — humanist sans',
            'Merriweather'       => 'Merriweather — serif',
            'Playfair Display'   => 'Playfair Display — display serif',
            'Cormorant Garamond' => 'Cormorant Garamond — editorial serif',
        ];
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
     * Where to send the merchant after saving step $thisStep.
     *
     * Two modes:
     *   - Linear progression (current step ≤ this step) → next step in
     *     ONBOARDING_STEPS.
     *   - Editing from review (current step ≥ launch, but this step is
     *     earlier) → straight back to /onboarding/launch. Without this,
     *     "Edit" from launch would dump the merchant into the next sequential
     *     step instead of returning to the review screen they came from.
     */
    private function nextStepUrl(string $thisStep): string
    {
        $tenant = $this->tenant();
        $launchIdx  = array_search('launch', Tenant::ONBOARDING_STEPS, true);
        $thisIdx    = array_search($thisStep, Tenant::ONBOARDING_STEPS, true);
        $currentIdx = array_search($tenant->onboarding_step, Tenant::ONBOARDING_STEPS, true);

        if ($currentIdx !== false && $thisIdx !== false && $launchIdx !== false
            && $currentIdx >= $launchIdx && $thisIdx < $launchIdx) {
            return '/onboarding/launch';
        }

        $nextIdx = ($thisIdx !== false) ? $thisIdx + 1 : 0;
        if ($nextIdx >= count(Tenant::ONBOARDING_STEPS) - 1) {
            return '/onboarding';
        }
        return '/onboarding/' . Tenant::ONBOARDING_STEPS[$nextIdx];
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
            'currency'   => 'EUR',
            'is_active'  => true,
            'image_path' => null,
        ])));
    }
}
