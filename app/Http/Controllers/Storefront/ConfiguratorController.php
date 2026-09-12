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

        /*
         | A CODE NAMES ONE RACK, OR IT NAMES NOTHING.
         |
         | initialConfig() falls back to the store's defaults when a saved link
         | can no longer be built — the merchant has retired that height, say.
         | It used to do that while $saved stayed set, so the page rendered the
         | DEFAULT rack with the customer's code printed under it and their code
         | still in the address bar. The link did not break; it lied.
         |
         | $stale carries that fallback out to the view, which drops the code,
         | clears the address bar and says plainly that the saved rack is no
         | longer available.
         */
        [$config, $stale] = $this->initialConfig($saved, $limits);
        if ($stale) {
            $saved = null;
        }

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
            $stale = true;

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
            // The visitor arrived on a code this store can no longer build.
            'stale' => $stale,
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

        $snapshot = [
            'bom' => RackPresenter::labelledLines($quote),
            'subtotal_cents' => $quote->subtotalCents,
            'vat_cents' => $quote->vatCents,
            'total_cents' => $quote->totalCents,
            'vat_rate_bp' => $quote->vatRateBp,
            'currency' => $quote->currency,
        ];

        /*
         | THE SAME RACK IS THE SAME RACK — BUT ONLY FOR THE SAME PERSON.
         |
         | Pressing „Добави към заявката" twice used to produce two
         | configurations with two codes, and because the basket keys a line on
         | the code, the customer got two identical lines of one instead of one
         | line of two. Deduplicating fixed that and introduced something worse:
         | the match was made across the WHOLE TENANT and then written to. Two
         | strangers who happened to build the same rack shared one row, so
         | anybody could re-save that shape and rewrite a record somebody else
         | was holding a link to. A share link was editable by whoever had it.
         |
         | So the match is now scoped to this browser AND to an unchanged price,
         | and there is no update() left in this method. A stored configuration
         | is written once and never again: the only thing a save can do to an
         | existing row is decline to make another one.
         |
         | Matching on height, depth and levels in SQL and comparing the
         | segments in PHP: the segment list is a JSON column, and asking two
         | different database engines to agree on JSON equality is a worse bet
         | than reading back the handful of rows that share the other three.
         */
        $owner = RackConfiguration::ownerToken();
        $mine = RackConfiguration::reusableFor($store->tenant_id, $owner, $config, $snapshot);

        if ($mine) {
            return $mine;
        }

        return RackConfiguration::create($snapshot + [
            'tenant_id' => $store->tenant_id,
            'owner_token' => $owner,
            'height_cm' => $config->heightCm,
            'depth_cm' => $config->depthCm,
            'levels' => $config->levels,
            'segments' => $config->segments,
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

    /**
     * @return array{0:RackConfig,1:bool} the config, and whether a saved link
     *                                    had to be abandoned to produce it
     */
    private function initialConfig(?RackConfiguration $saved, array $limits): array
    {
        if (! $saved) {
            return [$this->defaultConfig($limits), false];
        }

        try {
            return [RackConfig::fromArray([
                'height' => $saved->height_cm,
                'depth' => $saved->depth_cm,
                'levels' => $saved->levels,
                'segments' => $saved->segmentWidths(),
            ], $limits), false];
        } catch (RackException $e) {
            // The merchant has retired a size this link depends on.
            return [$this->defaultConfig($limits), true];
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
