<?php

namespace App\Http\Controllers\StoreAdmin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Models\Category;
use App\Themes\ThemeCustomizer;
use App\Themes\ThemeRegistry;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;

/**
 * The merchant's own storefront, rendered INSIDE the Customize theme page.
 *
 * Why this exists: the customiser is a form of ~54 text boxes, and a shop owner
 * opening it cannot tell which box holds the sentence they want to change.
 * Reading a label like "Capability 03 — text" and finding it on the page is a
 * puzzle. So the page shows the actual storefront beside the form, every
 * editable string is clickable, and clicking one takes you to its field — or
 * lets you type straight into the page.
 *
 * This endpoint lives on the CENTRAL domain, not the tenant subdomain, because
 * that is where the Filament panel is and an iframe from another origin could
 * not be scripted. That means doing by hand what ResolveStorefrontTenant and
 * SetDisplayCurrency would normally have done — the same trick, and the same
 * reasons, as WizardController::themePreview().
 *
 * It renders the SAVED state. Unsaved edits are applied to the iframe's DOM by
 * the page's own script over postMessage, so typing is instant and no keystroke
 * costs a request.
 */
class ThemePreviewController extends Controller
{
    public function show(Request $request): ViewContract
    {
        $user = $request->user();
        $tenant = $user?->tenant;
        $store = $tenant?->store;

        abort_unless($tenant && $store, 404);

        $theme = $store->theme ?: 'default';
        abort_unless(ThemeRegistry::exists($theme), 404);

        // Tag every copy slot in the output. Set before the view renders, and
        // only ever here — a public storefront never reaches this line.
        ThemeCustomizer::enableEditMode();

        /*
         | Render in the STOREFRONT'S language, not the admin's.
         |
         | SetLocale decides on the request HOST, and this endpoint is on the
         | central domain, so it had already picked the panel's locale — a
         | merchant working in English got an English preview of a Bulgarian
         | shop, and every field would have looked like it held the wrong text.
         | Storefronts are Bulgarian-only (SetLocale::STOREFRONT).
         */
        App::setLocale(SetLocale::STOREFRONT[0] ?? SetLocale::DEFAULT);

        // Cart::forCurrent() and the storefront partials resolve through the
        // container binding the tenant middleware would normally have set.
        app()->instance('current_tenant', $tenant);

        $base = strtoupper($store->currency ?? 'EUR');
        View::share('displayCurrency', $base);
        View::share('displayRate', 1.0);
        View::share('baseCurrency', $base);

        $products = $tenant->products()
            ->where('is_active', true)
            ->with(['categories', 'variants'])
            ->take(12)
            ->get();

        // The theme index views expect a paginator (they call ->hasPages() and
        // friends), so wrap the snapshot in a single page rather than teach the
        // views about a second shape.
        $paginated = new LengthAwarePaginator(
            $products,
            $products->count(),
            max($products->count(), 1),
            1,
            ['path' => $request->url()]
        );

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view("themes.{$theme}.index", [
            'tenant' => $tenant,
            'store' => $store,
            'products' => $paginated,
            'filters' => [],
            'categories' => $categories,
            'featuredCollections' => collect(),
        ]);
    }
}
