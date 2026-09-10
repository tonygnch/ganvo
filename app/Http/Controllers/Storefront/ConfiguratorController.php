<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\RackConfiguration;
use App\Models\Store;
use App\Services\Cart;
use App\Services\Rack\RackCalculator;
use App\Services\Rack\RackConfig;
use App\Services\Rack\RackException;
use App\Services\Rack\RackPresenter;
use App\Services\Rack\RackPriceBook;
use App\Services\Rack\RackQuote;
use App\Themes\ThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The 2D rack configurator.
 *
 * The security shape of this controller is the point of it. The browser draws
 * a rack and shows a running price, but it never sends one: every endpoint here
 * takes a CONFIGURATION and derives the money itself from the price book (§36,
 * §37). A tampered payload can therefore only ask for a different rack, never
 * for a different price.
 *
 * Opt-in per store, like /about — an untouched store 404s rather than
 * publishing a configurator with no parts behind it.
 *
 * NOTE the method signatures: public methods on storefront controllers take
 * only a Request. The subdomain route group splices {tenantSlug} in
 * positionally, so a typed route parameter would receive the wrong value —
 * read them off the request instead.
 */
class ConfiguratorController extends Controller
{
    public function show(Request $request): View
    {
        [$store, $limits] = $this->gate();

        $prices = RackPriceBook::forTenant($store->tenant_id);
        // Only offer sizes the price book can actually quote, so the picker
        // cannot lead anywhere that errors three clicks later.
        $limits = $prices->narrow($limits);

        $saved = null;
        $code = trim((string) $request->route('code'));
        if ($code !== '') {
            $saved = RackConfiguration::where('tenant_id', $store->tenant_id)
                ->where('code', RackConfiguration::normaliseCode($code))
                ->first();
            abort_unless($saved, 404);
        }

        // Nothing the merchant still sells can be built: the page has no
        // product to configure, so it is not a page.
        abort_unless(RackPriceBook::canBuildAnything($limits), 404);

        $config = $this->initialConfig($saved, $limits);

        try {
            $quote = $this->calculator()->quote($config, $prices, $limits, $store->currency ?? 'EUR');
        } catch (RackException $e) {
            /*
             | A saved link whose parts have since been retired. Fall back to
             | the store's defaults — but GUARD the retry, because the defaults
             | are not automatically buildable either. This second try/catch is
             | not belt and braces: without it, deactivating the frame at the
             | store's own default size 500'd the page for every visitor, and
             | that is a single toggle on the merchant's price book screen.
             */
            $saved = null;

            try {
                $config = $this->defaultConfig($limits);
                $quote = $this->calculator()->quote($config, $prices, $limits, $store->currency ?? 'EUR');
            } catch (RackException $fatal) {
                abort(404);
            }
        }

        $tenant = app('current_tenant');
        $theme = $this->theme($store);

        return view($this->view($theme, 'configurator'), [
            'tenant' => $tenant,
            'store' => $store,
            'theme' => $theme,
            'limits' => $limits,
            'config' => $config,
            'quote' => $quote,
            'bom' => RackPresenter::labelledLines($quote),
            'saved' => $saved,
            // The browser mirrors this arithmetic for instant feedback. It is
            // the same price book the server just used, so the two agree —
            // and every state change is re-derived server-side anyway.
            'priceBook' => $this->priceBookPayload($prices, $limits),
        ]);
    }

    /** Re-price a configuration. The only thing the browser may ask about money. */
    public function quote(Request $request): JsonResponse
    {
        [$store, $limits] = $this->gate();

        try {
            $prices = RackPriceBook::forTenant($store->tenant_id);
            $config = RackConfig::fromArray((array) $request->input('config', []), $limits);
            $quote = $this->calculator()->quote($config, $prices, $limits, $store->currency ?? 'EUR');
        } catch (RackException $e) {
            return response()->json(['ok' => false, 'reason' => $this->reason($e, $limits)], 422);
        }

        return response()->json(['ok' => true] + $this->quotePayload($quote));
    }

    /** Persist a configuration and hand back its share link (§27, §28). */
    public function save(Request $request): JsonResponse
    {
        [$store, $limits] = $this->gate();

        try {
            $saved = $this->persist($request, $store, $limits);
        } catch (RackException $e) {
            return response()->json(['ok' => false, 'reason' => $this->reason($e, $limits)], 422);
        }

        return response()->json([
            'ok' => true,
            'code' => $saved->code,
            'url' => url('/configurator/'.$saved->code),
        ]);
    }

    /** Persist, then put the rack in the cart as one line (§26). */
    public function addToCart(Request $request): JsonResponse
    {
        [$store, $limits] = $this->gate();

        try {
            $saved = $this->persist($request, $store, $limits);
        } catch (RackException $e) {
            return response()->json(['ok' => false, 'reason' => $this->reason($e, $limits)], 422);
        }

        $cart = Cart::forCurrent();
        $cart->addRack($saved->code);

        return response()->json([
            'ok' => true,
            'code' => $saved->code,
            'url' => url('/configurator/'.$saved->code),
            // The figure the server actually banked, so the page can reconcile
            // its optimistic total instead of quietly disagreeing with the cart.
            'total_cents' => (int) $saved->total_cents,
            'item_count' => $cart->itemCount(),
            'message' => __('site.storefront.sankevi.cfg_added'),
        ]);
    }

    /**
     * Validate, re-price from the DB, and store. Both save and add-to-cart go
     * through here, so neither can bank a price the calculator did not produce.
     */
    private function persist(Request $request, $store, array $limits): RackConfiguration
    {
        $prices = RackPriceBook::forTenant($store->tenant_id);
        $config = RackConfig::fromArray((array) $request->input('config', []), $limits);
        $quote = $this->calculator()->quote($config, $prices, $limits, $store->currency ?? 'EUR');

        return RackConfiguration::create([
            'tenant_id' => $store->tenant_id,
            'height_cm' => $config->heightCm,
            'depth_cm' => $config->depthCm,
            'levels' => $config->levels,
            'segments' => $config->segments,
            'bom' => RackPresenter::labelledLines($quote),
            'subtotal_cents' => $quote->subtotalCents,
            'vat_cents' => $quote->vatCents,
            'total_cents' => $quote->totalCents,
            'vat_rate_bp' => $quote->vatRateBp,
            'currency' => $quote->currency,
        ]);
    }

    /**
     * What to tell the customer. The over-length refusal is the one message the
     * merchant may have rewritten (S21) — the rest is platform copy.
     */
    private function reason(RackException $e, array $limits): string
    {
        if ($e->reasonKey === 'cfg_err_too_long' && ($limits['over_limit_text'] ?? '') !== '') {
            return $limits['over_limit_text'];
        }

        return $e->translate();
    }

    /** @return array{0:Store,1:array} */
    private function gate(): array
    {
        $store = app('current_tenant')->store;
        $limits = $store->rackConfigurator();

        abort_unless($limits['enabled'], 404);

        return [$store, $limits];
    }

    private function calculator(): RackCalculator
    {
        return new RackCalculator;
    }

    private function initialConfig(?RackConfiguration $saved, array $limits): RackConfig
    {
        if (! $saved) {
            return $this->defaultConfig($limits);
        }

        try {
            return RackConfig::fromArray([
                'height' => $saved->height_cm,
                'depth' => $saved->depth_cm,
                'levels' => $saved->levels,
                'segments' => $saved->segmentWidths(),
            ], $limits);
        } catch (RackException $e) {
            // The merchant has retired a size this link depends on.
            return $this->defaultConfig($limits);
        }
    }

    private function defaultConfig(array $limits): RackConfig
    {
        return RackConfig::of(
            $limits['default_height_cm'],
            $limits['default_depth_cm'],
            $limits['default_levels'],
            [$limits['default_width_cm']],
        );
    }

    private function quotePayload(RackQuote $quote): array
    {
        return $quote->toArray() + ['bom' => RackPresenter::labelledLines($quote)];
    }

    /**
     * The price table the browser prices against. Public information — it is
     * the merchant's own list — and shipping it is what lets the total move
     * on every click without a round trip (§25).
     */
    private function priceBookPayload(RackPriceBook $prices, array $limits): array
    {
        $frames = [];
        foreach ($limits['heights'] as $h) {
            foreach ($limits['depths'] as $d) {
                try {
                    $frames["{$h}x{$d}"] = $prices->frame($h, $d)->price_cents;
                } catch (RackException $e) {
                    // Not offered; narrow() has already hidden it from the picker.
                }
            }
        }

        $shelves = [];
        foreach ($limits['widths'] as $w) {
            foreach ($limits['depths'] as $d) {
                $rw = max(1, $w - $limits['shelf_width_trim_cm']);
                $rd = max(1, $d - $limits['shelf_depth_trim_cm']);
                try {
                    $shelves["{$rw}x{$rd}"] = $prices->shelf($rw, $rd)->price_cents;
                } catch (RackException $e) {
                }
            }
        }

        $flat = [];
        foreach (['end_pin', 'extension_pin', 'cross_brace'] as $kind) {
            try {
                $flat[$kind] = $prices->flat($kind)->price_cents;
            } catch (RackException $e) {
                $flat[$kind] = null;
            }
        }

        return ['frames' => $frames, 'shelves' => $shelves, 'flat' => $flat];
    }

    private function theme($store): string
    {
        $theme = (string) ($store->theme ?? 'default');

        return ThemeRegistry::exists($theme) ? $theme : 'default';
    }

    private function view(string $theme, string $relative): string
    {
        return view()->exists("themes.{$theme}.{$relative}")
            ? "themes.{$theme}.{$relative}"
            : "storefront.{$relative}";
    }
}
