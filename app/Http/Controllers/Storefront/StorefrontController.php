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
        return $this->catalogue($request, forceShop: false);
    }

    /**
     * The catalogue at /shop. Same data as index(), but always the shop view
     * when the theme provides one — a shopper who clicked "Shop" wants the
     * grid, not the brand page.
     */
    public function shop(Request $request): View
    {
        return $this->catalogue($request, forceShop: true);
    }

    /**
     * The shared body of index() and shop().
     *
     * This MUST stay private with both entry points taking only a Request:
     * the storefront is also registered under a `{tenantSlug}.domain` route,
     * and Laravel splices leftover route parameters positionally into any
     * extra controller arguments. A public `index(Request, bool $forceShop)`
     * therefore received the tenant slug as $forceShop — a non-empty string,
     * i.e. true — and the landing page rendered the catalogue on every
     * subdomain request.
     */
    private function catalogue(Request $request, bool $forceShop): View
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->themeFor($store);

        // The whole catalogue opens in the merchant's category order, so the
        // shop reads the way the category chips above it do.
        $filters = $this->extractFilters($request, 'category');
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

        // A theme may split its landing page from its catalogue: when the
        // shopper is browsing (searching, filtering, paginating) send them to
        // the shop view if the theme has one, so the brand page does not have
        // to double as a product grid. Themes without a shop view are
        // unaffected — index still renders both, exactly as before.
        $browsing = $forceShop || $this->isBrowsing($filters, $products);
        $view = ($browsing && view()->exists("themes.{$theme}.shop"))
            ? "themes.{$theme}.shop"
            : "themes.{$theme}.index";

        return view($view, compact(
            'tenant', 'store', 'products', 'categories', 'filters', 'featuredCollections'
        ));
    }

    /** True when the shopper is filtering/searching/paging rather than landing. */
    private function isBrowsing(array $filters, $products): bool
    {
        return ($filters['q'] ?? null)
            || ($filters['category'] ?? null)
            || ($filters['min_price'] ?? null) !== null
            || ($filters['max_price'] ?? null) !== null
            || ($filters['in_stock'] ?? false)
            /*
             | AGAINST THIS LISTING'S RESTING ORDER, not against 'newest'.
             |
             | A sort the shopper picked means they are browsing, and the theme
             | sends them to the shop view instead of the brand page. The
             | catalogue's default is now 'category', so comparing with
             | 'newest' made every arrival look like a deliberate sort — and
             | the landing page rendered the product grid.
             */
            || (($filters['sort'] ?? 'category') !== 'category')
            || (method_exists($products, 'currentPage') && $products->currentPage() > 1);
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
     * $default is what "no choice" means, and it is not the same everywhere:
     * the full catalogue opens in the merchant's own category order, while a
     * category page — where every product shares one category and that order
     * says nothing — opens newest-first as it always has.
     *
     * @return array{q: ?string, sort: string, category: ?string, min_price: ?int, max_price: ?int, in_stock: bool}
     */
    private function extractFilters(Request $request, string $default = 'newest'): array
    {
        $sort = $request->query('sort');
        if (! in_array($sort, ['newest', 'price_asc', 'price_desc', 'name_asc', 'category'], true)) {
            $sort = $default;
        }

        // Prices arrive in major units (€9.99); convert to cents to match
        // the column. Null when blank so we don't filter on 0.
        $toCents = fn ($v) => ($v === null || $v === '') ? null : (int) round(((float) $v) * 100);

        return [
            'q' => trim((string) $request->query('q', '')) ?: null,
            'sort' => $sort,
            'category' => trim((string) $request->query('category', '')) ?: null,
            'min_price' => $toCents($request->query('min_price')),
            'max_price' => $toCents($request->query('max_price')),
            'in_stock' => $request->query('in_stock') === '1',
        ];
    }

    private function buildProductQuery($tenant, array $filters)
    {
        $query = $tenant->products()->where('is_active', true);

        if ($filters['q']) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']).'%';
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

        return $this->applySort($query, $filters['sort']);
    }

    /**
     * The one place a product list decides its order.
     *
     * Columns are qualified because a category page's query reaches products
     * through the pivot, where a bare `name` has more than one candidate.
     */
    private function applySort($query, string $sort)
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('products.price_cents'),
            'price_desc' => $query->orderByDesc('products.price_cents'),
            'name_asc' => $query->orderBy('products.name'),
            'category' => $this->orderByCategoryOrder($query),
            default => $query->orderByDesc('products.created_at')->orderByDesc('products.id'),
        };
    }

    /**
     * Order products by where their category sits in the merchant's own
     * running order — the same order the category chips are drawn in.
     *
     * A CHILD CATEGORY'S sort_order IS SCOPED TO ITS SIBLINGS, so it cannot be
     * compared with a root's: Sankevi has a "Плотове за маси" nested under
     * "Плотове от масив" at sort_order 0, which read as first-in-the-shop
     * rather than fourth, inside its parent. So the key resolves to the root
     * first and only then places the child within it, and a root sorts ahead
     * of its own children.
     *
     * min() picks a lane for a product filed under several categories — the
     * earliest one it belongs to, which is where a shopper scanning the
     * catalogue in category order would first look for it.
     *
     * Soft-deleted categories are excluded: their pivot rows outlive them, and
     * a deleted category should not still be placing products. A product with
     * no category at all sorts last rather than first, which is what NULL
     * would otherwise do in both MySQL and SQLite.
     */
    private function orderByCategoryOrder($query)
    {
        $join = 'from categories c
                 inner join category_product cp on cp.category_id = c.id
                 where cp.product_id = products.id
                   and c.deleted_at is null';

        $root = '(select min(coalesce(parent.sort_order, c.sort_order))
                    from categories c
                    left join categories parent on parent.id = c.parent_id
                    inner join category_product cp on cp.category_id = c.id
                   where cp.product_id = products.id
                     and c.deleted_at is null)';

        // -1, not 0: a child may itself sit at sort_order 0, and then "the
        // parent's own products come first" would rest on the tiebreaker
        // below rather than on the rule.
        $within = "(select min(case when c.parent_id is null then -1 else c.sort_order end) {$join})";

        return $query
            ->orderByRaw("coalesce({$root}, 2147483647)")
            ->orderByRaw("coalesce({$within}, 0)")
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id');
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

        // Pull the same filter set the home page uses (search / sort /
        // price / in-stock) so the category page can host its own filter
        // sidebar. The `category` filter param is intentionally ignored
        // here — the path-segment slug already scopes products to this
        // category and we don't want the dropdown to override it.
        $filters = $this->extractFilters($request);

        // Products directly attached to this category. We don't auto-
        // include descendants — operators can have a "Mens" parent with
        // T-shirts/Pants/Shoes children, and the parent page should be
        // empty unless they explicitly tag products to it. Avoids
        // surprise inclusions.
        $query = $category->products()->where('is_active', true);

        if ($filters['q']) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term);
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
        $query = $this->applySort($query, $filters['sort']);

        $products = $query->paginate(12)->withQueryString();

        $categories = $this->rootCategoriesFor($tenant);

        // Theme-specific category view if it exists, generic otherwise.
        $view = view()->exists("themes.{$theme}.category")
            ? "themes.{$theme}.category"
            : 'storefront.category';

        return view($view, compact('tenant', 'store', 'category', 'products', 'categories', 'filters'));
    }

    private function themeFor($store): string
    {
        return ThemeRegistry::exists($store->theme) ? $store->theme : 'default';
    }

    /**
     * The category spine: roots in the merchant's order, each carrying its own
     * children.
     *
     * The children are eager-loaded rather than flattened in because a theme
     * that draws this as a top-level nav wants the roots alone, and one that
     * draws a browsable index wants the whole tree. Loading them costs a single
     * extra query and lets each theme decide; leaving them out meant a
     * subcategory simply had no way to be reached from the shop.
     */
    private function rootCategoriesFor($tenant)
    {
        return Category::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
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
