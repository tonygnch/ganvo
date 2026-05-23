<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Themes\ThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->themeFor($store);

        $products = $tenant->products()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Active root categories surfaced to the home + nav. The theme
        // layout reads this from view('categories') via View::share OR
        // we pass it explicitly so themes that don't expect it don't
        // break. Limit to roots so the home nav stays compact; child
        // categories are reachable from each parent's page.
        $categories = $this->rootCategoriesFor($tenant);

        return view("themes.{$theme}.index", compact('tenant', 'store', 'products', 'categories'));
    }

    public function product(Request $request): View
    {
        $slug = $request->route('slug');
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->themeFor($store);

        $product = $tenant->products()
            ->with('categories')
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
            ->get();

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
}
