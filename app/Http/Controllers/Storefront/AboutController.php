<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Themes\ThemeRegistry;
use Illuminate\View\View;

/**
 * Storefront history / about page — the merchant's own story, milestones
 * and numbers.
 *
 * Read-only, no form and nothing to persist: everything on the page comes
 * out of the store's `about` JSON. The page is opt-in (see
 * Store::aboutPage()), so an untouched store 404s here rather than
 * publishing an empty history.
 */
class AboutController extends Controller
{
    public function show(): View
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $about = $store->aboutPage();

        abort_unless($about['enabled'], 404);

        $theme = $this->theme($store);

        return view(
            $this->view($theme, 'about'),
            compact('tenant', 'store', 'theme', 'about')
        );
    }

    /** Active theme slug, falling back to 'default' for unknown values. */
    private function theme($store): string
    {
        $theme = (string) ($store->theme ?? 'default');

        return ThemeRegistry::exists($theme) ? $theme : 'default';
    }

    /** Theme-specific view when it exists, otherwise the shared fallback. */
    private function view(string $theme, string $relative): string
    {
        return view()->exists("themes.{$theme}.{$relative}")
            ? "themes.{$theme}.{$relative}"
            : "storefront.{$relative}";
    }
}
