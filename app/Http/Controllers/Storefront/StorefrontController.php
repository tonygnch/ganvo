<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Themes\ThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    /**
     * Storefront index — paginated, filterable, sortable product grid.
     *
     * Query params (all optional, all GET so URLs are shareable):
     *   q          search string; matches name + description (LIKE)
     *   sort       newest | price_asc | price_desc | name_asc
     *   category   category slug (single)
     *   min_price  in major units (e.g. 9.99); converted to cents
     *   max_price  same
     *   in_stock   "1" → only stock > 0
     */
    public function index(Request $request): View
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->themeFor($store);

        $filters = $this->extractFilters($request);
        $query = $this->buildProductQuery($tenant, $filters);

        // 12 per page is a clean 3×4 / 4×3 grid on most themes; small
        // enough to keep first paint fast on mobile.
        $products = $query->paginate(12)->withQueryString();

        $categories = $this->rootCategoriesFor($tenant);

        // Featured collections render as named strips on the home page
        // (themes only show them on the unfiltered landing, so it's fine
        // to query them eagerly here). Only those marked is_featured —
        // non-featured ones still live at /collections/{slug} via
        // collection() below.
        $featuredCollections = $this->featuredCollectionsFor($tenant);

        return view("themes.{$theme}.index", compact(
            'tenant', 'store', 'products', 'categories', 'filters', 'featuredCollections'
        ));
    }

    /**
     * /collections/{slug} — single curated collection's products page.
     * Mirrors the category() flow so themes that ship a custom
     * `themes.{theme}.collection` view get used; otherwise the generic
     * `storefront.collection` template renders inside the theme layout.
     */
    public function collection(Request $request): View
    {
        $slug = $request->route('slug');
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->themeFor($store);

        $collection = Collection::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Only active products — and respect the pivot sort order set
        // by the operator in the admin picker.
        $products = $collection->products()
            ->where('is_active', true)
            ->paginate(12)
            ->withQueryString();

        $categories = $this->rootCategoriesFor($tenant);

        $view = view()->exists("themes.{$theme}.collection")
            ? "themes.{$theme}.collection"
            : 'storefront.collection';

        return view($view, compact('tenant', 'store', 'collection', 'products', 'categories'));
    }

    /**
     * Extract + normalize the filter query params into a stable shape
     * the view can rely on. Always present keys; nullable values where
     * "no filter applied" makes sense.
     *
     * @return array{q: ?string, sort: string, category: ?string, min_price: ?int, max_price: ?int, in_stock: bool}
     */
    private function extractFilters(Request $request): array
    {
        $sort = $request->query('sort');
        if (! in_array($sort, ['newest', 'price_asc', 'price_desc', 'name_asc'], true)) {
            $sort = 'newest';
        }

        // Prices arrive in major units (€9.99); convert to cents to match
        // the column. Null when blank so we don't filter on 0.
        $toCents = fn ($v) => ($v === null || $v === '') ? null : (int) round(((float) $v) * 100);

        return [
            'q'         => trim((string) $request->query('q', '')) ?: null,
            'sort'      => $sort,
            'category'  => trim((string) $request->query('category', '')) ?: null,
            'min_price' => $toCents($request->query('min_price')),
            'max_price' => $toCents($request->query('max_price')),
            'in_stock'  => $request->query('in_stock') === '1',
        ];
    }

    private function buildProductQuery($tenant, array $filters)
    {
        $query = $tenant->products()->where('is_active', true);

        if ($filters['q']) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        if ($filters['category']) {
            // EXISTS subquery on the pivot — avoids a join that'd
            // duplicate rows when a product is in multiple categories.
            $query->whereHas('categories', function ($q) use ($filters, $tenant) {
                $q->where('slug', $filters['category'])
                  ->where('tenant_id', $tenant->id);
            });
        }

        if ($filters['min_price'] !== null) {
            $query->where('price_cents', '>=', $filters['min_price']);
        }
        if ($filters['max_price'] !== null) {
            $query->where('price_cents', '<=', $filters['max_price']);
        }
        if ($filters['in_stock']) {
            $query->where('stock_quantity', '>', 0);
        }

        return match ($filters['sort']) {
            'price_asc'  => $query->orderBy('price_cents'),
            'price_desc' => $query->orderByDesc('price_cents'),
            'name_asc'   => $query->orderBy('name'),
            default      => $query->orderByDesc('created_at'),
        };
    }

    public function product(Request $request): View
    {
        $slug = $request->route('slug');
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->themeFor($store);

        $product = $tenant->products()
            ->with(['categories', 'gallery'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $categories = $this->rootCategoriesFor($tenant);

        return view("themes.{$theme}.product", compact('tenant', 'store', 'product', 'categories'));
    }

    /**
     * /categories/{slug} — products in a single category. Uses a generic
     * `themes.{theme}.category` view if the theme ships one, otherwise
     * falls back to a shared template. Children categories (if any) are
     * surfaced as chips at the top.
     */
    public function category(Request $request): View
    {
        $slug = $request->route('slug');
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->themeFor($store);

        $category = Category::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Products directly attached to this category. We don't auto-
        // include descendants — operators can have a "Mens" parent with
        // T-shirts/Pants/Shoes children, and the parent page should be
        // empty unless they explicitly tag products to it. Avoids
        // surprise inclusions.
        $products = $category->products()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        $categories = $this->rootCategoriesFor($tenant);

        // Theme-specific category view if it exists, generic otherwise.
        $view = view()->exists("themes.{$theme}.category")
            ? "themes.{$theme}.category"
            : 'storefront.category';

        return view($view, compact('tenant', 'store', 'category', 'products', 'categories'));
    }

    private function themeFor($store): string
    {
        return ThemeRegistry::exists($store->theme) ? $store->theme : 'default';
    }

    private function rootCategoriesFor($tenant)
    {
        return Category::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Featured collections (active + is_featured) with a small bounded
     * preview of products for the homepage strip. Limit to 8 products
     * per strip so the home doesn't turn into a wall — the strip's
     * "view all" link goes to the full /collections/{slug} page.
     */
    private function featuredCollectionsFor($tenant)
    {
        return Collection::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->with(['products' => function ($q) {
                $q->where('is_active', true)->limit(8);
            }])
            ->get();
    }
}
