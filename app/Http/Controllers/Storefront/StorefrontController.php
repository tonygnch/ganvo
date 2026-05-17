<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Themes\ThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = ThemeRegistry::exists($store->theme) ? $store->theme : 'default';

        $products = $tenant->products()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return view("themes.{$theme}.index", compact('tenant', 'store', 'products'));
    }

    public function product(Request $request): View
    {
        $slug = $request->route('slug');
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = ThemeRegistry::exists($store->theme) ? $store->theme : 'default';

        $product = $tenant->products()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view("themes.{$theme}.product", compact('tenant', 'store', 'product'));
    }
}
