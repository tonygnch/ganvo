<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RackConfiguration;
use App\Models\RackPart;
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
use Illuminate\Support\Facades\Auth;
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
 * Drawing and pricing are open to anybody. KEEPING a rack — saving it, sharing
 * it, adding it to a request — takes a customer account, so every stored rack
 * has a name, an email and a phone behind it that the yard can call back. The
 * page asks a guest to sign up in place; this controller is what makes that
 * more than a suggestion.
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
            // Whether Save / Copy link / Add to request can go straight
            // through, or must open the sign-up window first. Only a hint to
            // the page: save() and addToCart() check again for themselves.
            'signedIn' => $this->customer($store) !== null,
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

        if (! $customer = $this->customer($store)) {
            return $this->signInFirst();
        }

        try {
            // Save writes over the rack the page opened, when that is allowed —
            // see persist(). Add-to-cart never does: two racks in a request stay two.
            $editing = $request->input('editing');
            $saved = $this->persist($request, $store, $limits, $customer, is_string($editing) ? $editing : null);
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

        if (! $customer = $this->customer($store)) {
            return $this->signInFirst();
        }

        try {
            $saved = $this->persist($request, $store, $limits, $customer);
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
     *
     * @param  ?string  $editing  the code of the rack the page opened and is saving over — save only
     */
    private function persist(Request $request, $store, array $limits, Customer $customer, ?string $editing = null): RackConfiguration
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

        $attributes = [
            'type' => $config->type,
            'desk_depth_cm' => $config->deskDepthCm,
            'height_cm' => $config->heightCm,
            'depth_cm' => $config->depthCm,
            'levels' => $config->levels,
            'segments' => $config->segments,
            'extra_braces' => $config->extraBraces ?: null,
            'desk_sections' => $config->deskSections ?: null,
        ];

        /*
         | SAVING OVER A RACK YOU OPENED UPDATES IT — WHILE IT IS STILL YOURS TO CHANGE.
         |
         | Opening one of your racks, changing it and pressing „Запази" used to
         | leave the old one behind and hand you a new code, so every edit added
         | another rack to the account. Now the save writes over the one opened:
         | same code, same link, the new shape and today's price.
         |
         | Only when all of these hold — otherwise it falls through to the rules
         | below and makes a new rack, leaving the opened one exactly as it was:
         |   - it is THIS customer's rack. A shared link opened by somebody else
         |     is theirs to copy, never to rewrite;
         |   - it has never been requested. Once a rack is on an order, the
         |     merchant has to keep seeing what was asked for.
         */
        if ($editing !== null && $editing !== '') {
            $opened = RackConfiguration::where('tenant_id', $store->tenant_id)
                ->where('code', RackConfiguration::normaliseCode($editing))
                ->where('customer_id', $customer->id)
                ->doesntHave('orderItems')
                ->first();

            if ($opened) {
                $opened->update($snapshot + $attributes);

                return $opened;
            }
        }

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
         | So the match is scoped to this customer AND to an unchanged price,
         | and it never writes: the only thing it can do to an existing row is
         | decline to make another one. The one write a save may make is the
         | owner saving over the rack they opened, above.
         */
        $mine = RackConfiguration::reusableFor($store->tenant_id, $customer->id, $config, $snapshot);

        if ($mine) {
            return $mine;
        }

        return RackConfiguration::create($snapshot + $attributes + [
            'tenant_id' => $store->tenant_id,
            'customer_id' => $customer->id,
            'owner_token' => RackConfiguration::ownerToken(),
        ]);
    }

    /**
     * The signed-in customer, if they belong to THIS shop.
     *
     * Customer accounts are per tenant, but the guard only knows "somebody is
     * logged in". A session carried over from another storefront must count as
     * nobody here, or one shop's customer would be saving racks into another
     * shop's list under an account that shop has never seen.
     */
    private function customer(Store $store): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer instanceof Customer && (int) $customer->tenant_id === (int) $store->tenant_id
            ? $customer
            : null;
    }

    /**
     * The refusal the page turns into the sign-up window. 401 rather than a
     * redirect: these are JSON calls, and a redirect would hand fetch() the
     * login page's HTML to choke on.
     */
    private function signInFirst(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'auth_required' => true,
            'reason' => __('site.storefront.sankevi.cfg_auth_required'),
        ], 401);
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
                'type' => $saved->type ?: RackConfig::TYPE_SINGLE,
                'desk_depth' => $saved->desk_depth_cm,
                'height' => $saved->height_cm,
                'depth' => $saved->depth_cm,
                'levels' => $saved->levels,
                'segments' => $saved->segmentWidths(),
                'extra_braces' => $saved->extraBraceIndexes(),
                'desk_sections' => $saved->type === 'office' ? $saved->deskSectionIndexes() : null,
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
            $limits['default_type'] ?? RackConfig::TYPE_SINGLE,
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
        /* Per rack type, as the merchant prices them: frames by height × depth,
           and the type's board — shelves, or the wine rack's trays — by real
           width × depth. The office desk needs no table of its own: it is a
           deeper shelf, priced from the office rack's shelves. */
        $frames = [];
        $boards = [];
        foreach ($limits['by_type'] ?? [] as $type => $sizes) {
            $frames[$type] = [];
            $boards[$type] = [];
            $boardKind = RackConfig::modelBoardKind($type) ?? RackPart::KIND_SHELF;

            foreach ($sizes['heights'] as $h) {
                foreach ($sizes['depths'] as $d) {
                    if ($prices->has(RackPart::keyFor(RackPart::KIND_FRAME, $h, $d, null, $type))) {
                        $frames[$type]["{$h}x{$d}"] = $prices->frame($h, $d, $type)->price_cents;
                    }
                }
            }

            foreach ($sizes['widths'] as $w) {
                foreach ($sizes['depths'] as $d) {
                    $rw = max(1, $w - $limits['shelf_width_trim_cm']);
                    $rd = max(1, $d - $limits['shelf_depth_trim_cm']);
                    if ($prices->has(RackPart::keyFor($boardKind, null, $rd, $rw, $type))) {
                        $boards[$type]["{$rw}x{$rd}"] = $prices->board($boardKind, $rw, $rd, $type)->price_cents;
                    }
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

        return [
            'frames' => $frames,
            'boards' => $boards,
            'flat' => $flat,
        ];
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
