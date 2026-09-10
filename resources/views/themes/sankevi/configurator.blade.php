{{-- Sankevi — the rack configurator. A drawing board, not a product page: the
     elevation is drawn to scale in centimetres, the parts list is the real one,
     and the price moves as the run grows. The panel on the right is the docket
     the yard would write out by hand. --}}
@php
    $title = __('site.storefront.sankevi.cfg_title');

    $vatRate = rtrim(rtrim(number_format($quote->vatRateBp / 100, 2, '.', ''), '0'), '.');
    $currency = $store->currency ?: 'EUR';

    // Everything the browser needs to draw and to price. The price book is the
    // merchant's own published list, so shipping it costs nothing — and it is
    // what lets the total move on every click without a round trip. It is never
    // trusted back: /configurator/quote, /save and /cart all re-derive.
    $boot = [
        'limits' => [
            'heights' => $limits['heights'],
            'depths' => $limits['depths'],
            'widths' => $limits['widths'],
            'levels' => $limits['levels'],
            'maxLength' => $limits['max_length_cm'],
            'widthTrim' => $limits['shelf_width_trim_cm'],
            'depthTrim' => $limits['shelf_depth_trim_cm'],
            'thicknessMm' => $limits['shelf_thickness_mm'],
            'vatRateBp' => $limits['vat_rate_bp'],
        ],
        'prices' => $priceBook,
        'config' => $config->toArray(),
        'labels' => [
            'frame' => __('site.storefront.sankevi.cfg_part_frame', ['h' => ':h', 'd' => ':d']),
            'shelf' => __('site.storefront.sankevi.cfg_part_shelf', ['w' => ':w', 'd' => ':d']),
            'end_pin' => __('site.storefront.sankevi.cfg_part_end_pin'),
            'extension_pin' => __('site.storefront.sankevi.cfg_part_extension_pin'),
            'cross_brace' => __('site.storefront.sankevi.cfg_part_cross_brace'),
            'section' => __('site.storefront.sankevi.cfg_section_n', ['n' => ':n']),
            // The merchant's own wording when they have written one — S21 lists
            // "texts" among the things they must be able to change themselves.
            'overLimit' => $limits['over_limit_text'] !== ''
                ? $limits['over_limit_text']
                : __('site.storefront.sankevi.cfg_over_limit', ['metres' => number_format($limits['max_length_cm'] / 100, 2)]),
            'copied' => __('site.storefront.sankevi.cfg_link_copied'),
            'saved' => __('site.storefront.sankevi.cfg_saved'),
            'qty' => __('site.storefront.sankevi.cfg_qty'),
            'vat' => __('site.storefront.sankevi.cfg_vat', ['rate' => ':rate']),
            'generic' => __('site.storefront.sankevi.cfg_err_generic'),
        ],
        'currency' => $currency,
        // The visitor may be reading the shop in another currency; the
        // configurator has to agree with the cart it feeds.
        'displayCurrency' => $displayCurrency ?? $currency,
        'displayRate' => (float) ($displayRate ?? 1.0),
        'locale' => app()->getLocale(),
        'savedCode' => $saved?->code,
        // This theme has no <meta name="csrf-token">; the rest of the
        // storefront rides the token in a form body. These endpoints take
        // JSON, so it travels in the header instead — same token, same guard.
        'token' => csrf_token(),
    ];
@endphp
@extends('themes.sankevi.layout')

@section('content')
<style>
    /* ===== the drawing board ===================================== */
    .cfg-head { position: relative; padding: 46px 0 22px; border-bottom: 1px solid var(--line); }
    .cfg-head h1 { font-family: var(--display); font-weight: 500; text-transform: uppercase;
        letter-spacing: .02em; line-height: .96; margin: 12px 0 0;
        font-size: clamp(30px, 5.4vw, 58px); }
    .cfg-head h1 em { font-style: normal; color: var(--accent); }
    .cfg-head .lead { max-width: 56ch; margin: 16px 0 0; color: var(--muted); line-height: 1.62; }

    .cfg-grid { display: grid; grid-template-columns: minmax(0, 1fr) 372px; gap: 26px;
        align-items: start; padding: 26px 0 60px; }

    .cfg-board { min-width: 0; }

    /* --- the four choices ------------------------------------- */
    .cfg-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .cfg-step label { display: block; font-family: var(--body); font-size: 10px; font-weight: 600;
        letter-spacing: .22em; text-transform: uppercase; color: var(--faint); margin-bottom: 7px; }
    .cfg-step select { width: 100%; }
    .cfg-note { margin: 10px 0 0; font-size: 12px; color: var(--faint); }

    /* --- the canvas ------------------------------------------- */
    .cfg-canvas { position: relative; margin-top: 16px; border: 1px solid var(--line);
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--surface) 70%, transparent), var(--bg));
        overflow: hidden; }
        /* pan-y, not none: the drag handler only ever moves the view sideways, so
       the browser keeps vertical scrolling and pinch-zoom. `none` swallowed
       both — on a phone the drawing is a third of the page and swiping up it
       did nothing at all. */
    .cfg-canvas svg { display: block; width: 100%; height: 420px; touch-action: pan-y; cursor: grab; }
    .cfg-canvas svg.dragging { cursor: grabbing; }

    .cfg-tools { position: absolute; right: 10px; top: 10px; display: flex; gap: 6px; z-index: 3; }
    .cfg-tools button { font-family: var(--body); font-size: 11px; font-weight: 600;
        letter-spacing: .14em; text-transform: uppercase; color: var(--txt);
        background: color-mix(in srgb, var(--surface) 88%, transparent);
        border: 1px solid var(--line2); padding: 7px 11px; cursor: pointer;
        backdrop-filter: blur(3px); }
    .cfg-tools button:hover { border-color: var(--accent); color: var(--accent); }
    .cfg-scale { position: absolute; left: 12px; bottom: 10px; z-index: 3;
        font-family: var(--body); font-size: 10px; letter-spacing: .2em;
        text-transform: uppercase; color: var(--faint); pointer-events: none; }

    /* --- section chips: the real edit affordance ---------------- */
    .cfg-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 14px; align-items: center; }
    .cfg-chip { font-family: var(--body); font-size: 12px; font-weight: 600; color: var(--muted);
        background: transparent; border: 1px solid var(--line2); padding: 8px 12px;
        min-width: 44px; min-height: 44px; cursor: pointer; line-height: 1.15; }
    .cfg-chip b { display: block; font-size: 10px; font-weight: 500; letter-spacing: .1em;
        color: var(--faint); }
    .cfg-chip[aria-pressed="true"] { border-color: var(--accent); color: var(--accent);
        background: color-mix(in srgb, var(--accent) 10%, transparent); }
    .cfg-chip.braced::after { content: "✕"; margin-left: 6px; font-size: 9px; opacity: .55; }
    .cfg-chip-add { border-style: dashed; color: var(--accent); border-color: var(--accent); }

    /* --- the section editor ------------------------------------ */
    .cfg-editor { margin-top: 14px; border: 1px solid var(--line); background: var(--surface);
        padding: 16px 18px; }
    .cfg-editor h2 { font-family: var(--display); font-weight: 500; text-transform: uppercase;
        letter-spacing: .08em; font-size: 15px; margin: 0 0 12px; }
    .cfg-editor-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
    .cfg-editor-row .cfg-step { flex: 0 1 190px; }
    .cfg-editor .btn { min-height: 44px; }

    /* --- the docket -------------------------------------------- */
    .cfg-panel { position: sticky; top: calc(var(--header-height) + 14px); border: 1px solid var(--line);
        background: var(--surface); }
    .cfg-panel-h { padding: 15px 18px; border-bottom: 1px solid var(--line);
        font-family: var(--display); font-weight: 500; text-transform: uppercase;
        letter-spacing: .1em; font-size: 14px; }
    .cfg-panel-b { padding: 16px 18px; }

    .cfg-facts { display: grid; grid-template-columns: 1fr auto; gap: 7px 14px; font-size: 13px; }
    .cfg-facts dt { color: var(--muted); }
    .cfg-facts dd { margin: 0; text-align: right; font-family: var(--display); font-weight: 500;
        letter-spacing: .04em; }

    .cfg-bom { margin-top: 16px; border-top: 1px solid var(--line); padding-top: 14px; }
    .cfg-bom h3 { font-family: var(--body); font-size: 10px; font-weight: 600; letter-spacing: .22em;
        text-transform: uppercase; color: var(--faint); margin: 0 0 10px; }
    .cfg-bom ul { list-style: none; margin: 0; padding: 0; }
    .cfg-bom li { display: grid; grid-template-columns: 42px 1fr auto; gap: 10px; align-items: baseline;
        padding: 6px 0; border-bottom: 1px solid color-mix(in srgb, var(--line) 60%, transparent);
        font-size: 13px; }
    .cfg-bom li:last-child { border-bottom: 0; }
    .cfg-bom .q { font-family: var(--display); font-weight: 500; color: var(--accent); }
    .cfg-bom .n { color: var(--txt); }
    .cfg-bom .p { color: var(--muted); font-size: 12px; white-space: nowrap; }

    .cfg-money { margin-top: 14px; border-top: 1px solid var(--line2); padding-top: 12px; }
    .cfg-money .row { display: flex; justify-content: space-between; gap: 14px; font-size: 13px;
        color: var(--muted); padding: 3px 0; }
    .cfg-money .row.total { margin-top: 8px; padding-top: 10px; border-top: 1px solid var(--line);
        color: var(--txt); font-family: var(--display); font-weight: 500;
        font-size: 22px; letter-spacing: .02em; }
    .cfg-pricenote { margin: 10px 0 0; font-size: 11px; line-height: 1.55; color: var(--faint); }

    .cfg-actions { margin-top: 14px; display: grid; gap: 8px; }
    .cfg-actions .btn { width: 100%; justify-content: center; min-height: 46px; }
    .cfg-share { display: flex; gap: 8px; }
    .cfg-share .btn { flex: 1 1 0; min-height: 42px; font-size: 11px; }
    .cfg-code { margin-top: 10px; font-size: 11px; color: var(--faint); text-align: center; }
    .cfg-code b { color: var(--accent); font-family: var(--display); letter-spacing: .12em; }

    .cfg-alert { margin-top: 12px; border: 1px solid var(--accent); padding: 11px 13px;
        font-size: 12px; line-height: 1.55; color: var(--txt);
        background: color-mix(in srgb, var(--accent) 12%, transparent); }
    .cfg-alert[hidden] { display: none !important; }

    /* --- the phone -------------------------------------------- */
    .cfg-bar { display: none; }

    @media (max-width: 1080px) {
        .cfg-grid { grid-template-columns: 1fr; }
        .cfg-panel { position: static; }
    }
    @media (max-width: 760px) {
        .cfg-steps { grid-template-columns: 1fr 1fr; }
        .cfg-canvas svg { height: 300px; }
        /* Over the rack on a narrow screen, and out of thumb reach. Bottom
           right instead — the scale readout keeps the other corner. */
        .cfg-tools { top: auto; bottom: 10px; }
        .cfg-head { padding: 34px 0 18px; }

        /* The docket stops being a sidebar and becomes a sticky till: the
           figures that matter follow you down the page, the rest folds away. */
        .cfg-panel { border-left: 0; border-right: 0; }
        .cfg-panel .cfg-actions .btn.primary-cta { display: none; }

        .cfg-bar { display: block; position: fixed; left: 0; right: 0; bottom: 0; z-index: 60;
            background: color-mix(in srgb, var(--surface) 96%, transparent);
            border-top: 1px solid var(--line2); backdrop-filter: blur(8px);
            padding: 10px 16px calc(10px + env(safe-area-inset-bottom)); }
        .cfg-bar-top { display: flex; justify-content: space-between; align-items: baseline;
            gap: 12px; margin-bottom: 8px; }
        .cfg-bar-meta { font-size: 11px; letter-spacing: .12em; text-transform: uppercase;
            color: var(--faint); }
        .cfg-bar-total { font-family: var(--display); font-weight: 500; font-size: 20px; }
        .cfg-bar .btn { width: 100%; justify-content: center; min-height: 48px; }
        .cfg-bar-see { display: block; width: 100%; margin-top: 8px; background: none; border: 0;
            color: var(--muted); font-size: 12px; text-decoration: underline;
            text-underline-offset: 3px; cursor: pointer; padding: 6px; min-height: 40px; }
    }
</style>

<main>
    <section class="cfg-head">
        <div class="wrap">
            {{-- editable(): the merchant's words when they have written any,
                 the platform's when they have not — and it is the one place
                 that decides escaping, so the _html slot keeps its <em> and
                 everything else is escaped without the call site choosing. --}}
            <span class="kicker">{!! $theme->editable('cfg_eyebrow') !!}</span>
            <h1>{!! $theme->editable('cfg_h1_html') !!}</h1>
            <p class="lead">{!! $theme->editable('cfg_lead') !!}</p>
        </div>
    </section>

    <div class="wrap">
        <div class="cfg-grid" data-cfg data-boot='@json($boot)'>

            {{-- ---------- the board ---------- --}}
            <div class="cfg-board">
                <div class="cfg-steps">
                    <div class="cfg-step">
                        <label for="cfgHeight">{{ __('site.storefront.sankevi.cfg_step_height') }}</label>
                        <select id="cfgHeight" data-cfg-height>
                            @foreach ($limits['heights'] as $h)
                                <option value="{{ $h }}" @selected($h === $config->heightCm)>{{ $h }} cm</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cfg-step">
                        <label for="cfgDepth">{{ __('site.storefront.sankevi.cfg_step_depth') }}</label>
                        <select id="cfgDepth" data-cfg-depth>
                            @foreach ($limits['depths'] as $d)
                                <option value="{{ $d }}" @selected($d === $config->depthCm)>{{ $d }} cm</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cfg-step">
                        <label for="cfgLevels">{{ __('site.storefront.sankevi.cfg_step_levels') }}</label>
                        <select id="cfgLevels" data-cfg-levels>
                            @foreach ($limits['levels'] as $l)
                                <option value="{{ $l }}" @selected($l === $config->levels)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cfg-step">
                        <label for="cfgNewWidth">{{ __('site.storefront.sankevi.cfg_step_width') }}</label>
                        <select id="cfgNewWidth" data-cfg-newwidth>
                            @foreach ($limits['widths'] as $w)
                                <option value="{{ $w }}" @selected($w === $limits['default_width_cm'])>{{ $w }} cm</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="cfg-note">{{ __('site.storefront.sankevi.cfg_shared_note') }}</p>

                <div class="cfg-canvas">
                    <div class="cfg-tools">
                        <button type="button" data-cfg-zoomout aria-label="{{ __('site.storefront.sankevi.cfg_zoom_out') }}">−</button>
                        <button type="button" data-cfg-fit>{{ __('site.storefront.sankevi.cfg_fit') }}</button>
                        <button type="button" data-cfg-zoomin aria-label="{{ __('site.storefront.sankevi.cfg_zoom_in') }}">+</button>
                    </div>
                    <svg data-cfg-svg role="img" aria-label="{{ __('site.storefront.sankevi.cfg_view_label') }}"
                         data-lenis-prevent preserveAspectRatio="xMidYMid meet"></svg>
                    <span class="cfg-scale" data-cfg-scale></span>
                </div>

                <div class="cfg-chips" data-cfg-chips role="group"
                     aria-label="{{ __('site.storefront.sankevi.cfg_summary_sections') }}"></div>

                <div class="cfg-editor" data-cfg-editor>
                    <h2 data-cfg-editor-title></h2>
                    <div class="cfg-editor-row">
                        <div class="cfg-step">
                            <label for="cfgEditWidth">{{ __('site.storefront.sankevi.cfg_edit_width') }}</label>
                            <select id="cfgEditWidth" data-cfg-editwidth>
                                @foreach ($limits['widths'] as $w)
                                    <option value="{{ $w }}">{{ $w }} cm</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn outline cut-sm" data-cfg-left>← {{ __('site.storefront.sankevi.cfg_move_left') }}</button>
                        <button type="button" class="btn outline cut-sm" data-cfg-right>{{ __('site.storefront.sankevi.cfg_move_right') }} →</button>
                        <button type="button" class="btn outline cut-sm" data-cfg-delete>{{ __('site.storefront.sankevi.cfg_delete_section') }}</button>
                    </div>
                </div>

                <div class="cfg-alert" data-cfg-alert hidden></div>
            </div>

            {{-- ---------- the docket ---------- --}}
            <aside class="cfg-panel">
                <div class="cfg-panel-h">{{ __('site.storefront.sankevi.cfg_panel_h') }}</div>
                <div class="cfg-panel-b">
                    <dl class="cfg-facts">
                        <dt>{{ __('site.storefront.sankevi.cfg_summary_height') }}</dt><dd data-cfg-f-height></dd>
                        <dt>{{ __('site.storefront.sankevi.cfg_summary_depth') }}</dt><dd data-cfg-f-depth></dd>
                        <dt>{{ __('site.storefront.sankevi.cfg_summary_length') }}</dt><dd data-cfg-f-length></dd>
                        <dt>{{ __('site.storefront.sankevi.cfg_summary_sections') }}</dt><dd data-cfg-f-sections></dd>
                        <dt>{{ __('site.storefront.sankevi.cfg_summary_levels') }}</dt><dd data-cfg-f-levels></dd>
                        <dt>{{ __('site.storefront.sankevi.cfg_summary_shelves') }}</dt><dd data-cfg-f-shelves></dd>
                    </dl>

                    <div class="cfg-bom" data-cfg-bomwrap>
                        <h3>{{ __('site.storefront.sankevi.cfg_set_includes') }}</h3>
                        <ul data-cfg-bom></ul>
                    </div>

                    <div class="cfg-money">
                        <div class="row"><span>{{ __('site.storefront.sankevi.cfg_subtotal') }}</span><span data-cfg-subtotal></span></div>
                        <div class="row"><span data-cfg-vatlabel></span><span data-cfg-vat></span></div>
                        <div class="row total"><span>{{ __('site.storefront.sankevi.cfg_total') }}</span><span data-cfg-total></span></div>
                    </div>
                    <p class="cfg-pricenote">{!! $theme->editable('cfg_price_note') !!}</p>

                    <div class="cfg-actions">
                        <button type="button" class="btn cut-sm primary-cta" data-cfg-addcart>{{ __('site.storefront.sankevi.cfg_add_to_cart') }}</button>
                        <div class="cfg-share">
                            <button type="button" class="btn outline cut-sm" data-cfg-save>{{ __('site.storefront.sankevi.cfg_save') }}</button>
                            <button type="button" class="btn outline cut-sm" data-cfg-copy>{{ __('site.storefront.sankevi.cfg_copy_link') }}</button>
                        </div>
                    </div>
                    <p class="cfg-code" data-cfg-code hidden>
                        {{ __('site.storefront.sankevi.cfg_code_label') }} <b data-cfg-codeval></b>
                    </p>
                </div>
            </aside>
        </div>
    </div>

    {{-- ---------- the sticky till (phone only) ---------- --}}
    <div class="cfg-bar" data-cfg-bar>
        <div class="cfg-bar-top">
            <span class="cfg-bar-meta" data-cfg-bar-meta></span>
            <span class="cfg-bar-total" data-cfg-bar-total></span>
        </div>
        <button type="button" class="btn cut-sm" data-cfg-addcart>{{ __('site.storefront.sankevi.cfg_add_to_cart') }}</button>
        <button type="button" class="cfg-bar-see" data-cfg-see>{{ __('site.storefront.sankevi.cfg_see_set') }}</button>
    </div>
</main>

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-cfg]');
    if (!root) { return; }

    var BOOT    = JSON.parse(root.dataset.boot);
    var LIMITS  = BOOT.limits;
    var PRICES  = BOOT.prices;
    var LABELS  = BOOT.labels;

    /* ===================================================================
     | STATE
     |
     | Nothing here is money. The browser derives a figure to show, but the
     | server derives the one that counts — every save and every add-to-cart
     | sends this object and nothing else.
     =================================================================== */
    var state = {
        height:   BOOT.config.height,
        depth:    BOOT.config.depth,
        levels:   BOOT.config.levels,
        segments: BOOT.config.segments.slice(),
        selected: 0,
        zoom:     1,
        panX:     null,   // null = follow the fit
        code:     BOOT.savedCode || null
    };

    /* Document-scoped on purpose: the sticky till on a phone sits OUTSIDE the
       configurator grid (it is position:fixed over the whole page), so scoping
       these to the grid left the only price a phone shows permanently blank. */
    var $  = function (sel) { return document.querySelector(sel); };
    var $$ = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };

    /* ===================================================================
     | THE SAME ARITHMETIC THE SERVER USES
     |
     | A deliberate mirror of App\Services\Rack\RackCalculator, kept short
     | enough to read side by side with it. Frames are shared, so a run of S
     | sections needs S+1 uprights; an extension pin is double-sided, which
     | is why it is 2·L·(S−1) and not 4·L·(S−1).
     |
     | If this ever drifts from the PHP, the debounced /configurator/quote
     | below overwrites it with the truth within half a second.
     =================================================================== */
    function shelfWidth(nominal) { return Math.max(1, nominal - LIMITS.widthTrim); }
    function shelfDepth(nominal) { return Math.max(1, nominal - LIMITS.depthTrim); }
    function totalLength() { return state.segments.reduce(function (a, b) { return a + b; }, 0); }
    function sections()    { return state.segments.length; }
    function frames()      { return sections() + 1; }
    function shelves()     { return sections() * state.levels; }
    function endPins()     { return 4 * state.levels; }
    function extPins()     { return 2 * state.levels * (sections() - 1); }
    function braces()      { return Math.ceil(sections() / 2); }
    function isBraced(i)   { return i % 2 === 0; }

    function bom() {
        var rows = [];
        var fk = state.height + 'x' + state.depth;
        rows.push({
            kind: 'frame',
            label: LABELS.frame.replace(':h', state.height).replace(':d', state.depth),
            qty: frames(),
            unit: PRICES.frames[fk]
        });

        var rd = shelfDepth(state.depth), byWidth = {};
        state.segments.forEach(function (w) {
            var rw = shelfWidth(w);
            byWidth[rw] = (byWidth[rw] || 0) + state.levels;
        });
        Object.keys(byWidth).map(Number).sort(function (a, b) { return a - b; }).forEach(function (rw) {
            rows.push({
                kind: 'shelf',
                label: LABELS.shelf.replace(':w', rw).replace(':d', rd),
                qty: byWidth[rw],
                unit: PRICES.shelves[rw + 'x' + rd]
            });
        });

        rows.push({ kind: 'end_pin', label: LABELS.end_pin, qty: endPins(), unit: PRICES.flat.end_pin });
        if (extPins() > 0) {
            rows.push({ kind: 'extension_pin', label: LABELS.extension_pin, qty: extPins(), unit: PRICES.flat.extension_pin });
        }
        rows.push({ kind: 'cross_brace', label: LABELS.cross_brace, qty: braces(), unit: PRICES.flat.cross_brace });

        var priced = true;
        rows.forEach(function (r) {
            if (typeof r.unit !== 'number') { priced = false; r.unit = 0; }
            r.subtotal = r.unit * r.qty;
        });

        var sub = rows.reduce(function (a, r) { return a + r.subtotal; }, 0);
        var vat = Math.round(sub * LIMITS.vatRateBp / 10000);
        return { rows: rows, subtotal: sub, vat: vat, total: sub + vat, priced: priced };
    }

    /* ---------- money ------------------------------------------------ */
    var fmt;
    try {
        fmt = new Intl.NumberFormat(BOOT.locale, { style: 'currency', currency: BOOT.displayCurrency });
    } catch (e) {
        fmt = { format: function (n) { return n.toFixed(2) + ' ' + BOOT.displayCurrency; } };
    }
    function money(cents) { return fmt.format((cents * BOOT.displayRate) / 100); }
    function metres(cm)   { return (cm / 100).toFixed(2) + ' m'; }

    /* ===================================================================
     | THE DRAWING
     |
     | One SVG user unit is one centimetre, so the elevation IS the
     | dimensions — an 18 mm board is 1.8 units thick and a 25 m run is 2500
     | wide. Zoom and pan move the viewBox rather than scaling the DOM, so
     | the drawing stays vector-crisp at any magnification.
     |
     | ---- THE ARTWORK SEAM -------------------------------------------
     | frameShape / shelfShape / braceShape are the only functions that
     | know what a part LOOKS like. When Sankevi supply traced SVG of the
     | real racks, these three bodies are replaced and nothing else in this
     | file changes.
     =================================================================== */
    /* An upright is 5 cm of timber. The bay PITCH is the nominal width, and a
       board is 3 cm shorter than its bay — so it lands 1.5 cm inside each pitch
       line and its ends rest ON the uprights, which is how the real rack works:
       the board sits on pins driven into the posts, it does not span a gap. */
    var POST_W  = 5;
    var INSET   = LIMITS.widthTrim / 2;          /* 1.5 cm each side */
    var THICK   = LIMITS.thicknessMm / 10;       /* 18 mm */
    var TOP_INSET = 6, BOT_INSET = 9, FOOT_H = 3.5;

    function esc(v) { return String(v).replace(/[<>&"]/g, ''); }

    function shelfYs() {
        var L = state.levels, H = state.height;
        var lowest  = H - BOT_INSET - THICK;
        var highest = TOP_INSET;
        if (L < 2) { return [ (H - THICK) / 2 ]; }
        var step = (lowest - highest) / (L - 1), out = [];
        for (var n = 0; n < L; n++) { out.push(highest + step * n); }
        return out;
    }

    function frameShape(cx, h) {
        var x = cx - POST_W / 2;
        var out = '<g class="pc-post">';
        out += '<rect class="pc-post-body" x="' + x + '" y="0" width="' + POST_W + '" height="' + h + '"/>';
        /* the lit edge and the shaded one — what makes a rectangle read as a
           squared length of timber rather than a line */
        out += '<rect class="pc-post-lit" x="' + x + '" y="0" width="' + (POST_W * 0.22) + '" height="' + h + '"/>';
        out += '<rect class="pc-post-shade" x="' + (x + POST_W * 0.74) + '" y="0" width="' + (POST_W * 0.26) + '" height="' + h + '"/>';
        /* drilled the whole way down: the holes are what makes the levels
           adjustable, so they belong in the drawing (S10) */
        var pitch = 5, r = POST_W * 0.11;
        for (var y = 5; y < h - FOOT_H - 2; y += pitch) {
            out += '<circle class="pc-hole" cx="' + cx + '" cy="' + y + '" r="' + r + '"/>';
        }
        /* the foot */
        out += '<rect class="pc-foot" x="' + (x - 0.6) + '" y="' + (h - FOOT_H) + '" width="' + (POST_W + 1.2) + '" height="' + FOOT_H + '"/>';
        return out + '</g>';
    }

    function shelfShape(x, y, w) {
        return '<g class="pc-board">' +
               '<rect class="pc-board-shadow" x="' + (x + 0.6) + '" y="' + (y + THICK) + '" width="' + w + '" height="' + (THICK * 0.5) + '"/>' +
               '<rect class="pc-board-body" x="' + x + '" y="' + y + '" width="' + w + '" height="' + THICK + '"/>' +
               '<rect class="pc-board-lit" x="' + x + '" y="' + y + '" width="' + w + '" height="' + (THICK * 0.32) + '"/>' +
               '</g>';
    }

    function braceShape(x, y, w, h) {
        return '<g class="pc-brace">' +
               '<line x1="' + x + '" y1="' + y + '" x2="' + (x + w) + '" y2="' + (y + h) + '"/>' +
               '<line x1="' + (x + w) + '" y1="' + y + '" x2="' + x + '" y2="' + (y + h) + '"/>' +
               '</g>';
    }

    function pinShape(cx, cy) {
        return '<rect class="pc-pin" x="' + (cx - POST_W * 0.30) + '" y="' + (cy - 0.45) + '" width="' + (POST_W * 0.60) + '" height="0.9" rx="0.3"/>';
    }

    function contentSize() {
        /* half a post overhangs each end of the run */
        return { w: totalLength() + POST_W, h: state.height };
    }

    function renderSvg() {
        var svg = $('[data-cfg-svg]');
        var size = contentSize();
        var ys = shelfYs();

        /* Pitch lines — one per upright. Half a post overhangs the left end,
           so the drawing starts at 0 and the first post is centred at POST_W/2. */
        var px = [POST_W / 2];
        state.segments.forEach(function (w, i) { px.push(px[i] + w); });

        var parts = [];
        parts.push(
            '<defs>' +
            '<linearGradient id="pcGrainV" x1="0" y1="0" x2="1" y2="0">' +
              '<stop offset="0" stop-color="var(--pc-wood-l)"/>' +
              '<stop offset="0.45" stop-color="var(--pc-wood-m)"/>' +
              '<stop offset="1" stop-color="var(--pc-wood-d)"/>' +
            '</linearGradient>' +
            '<linearGradient id="pcGrainH" x1="0" y1="0" x2="0" y2="1">' +
              '<stop offset="0" stop-color="var(--pc-wood-l)"/>' +
              '<stop offset="0.5" stop-color="var(--pc-wood-m)"/>' +
              '<stop offset="1" stop-color="var(--pc-wood-d)"/>' +
            '</linearGradient>' +
            '</defs>'
        );

        /* Braces sit on the BACK of the rack, so they go down first and
           everything else covers them (S33). Only every other bay carries one. */
        state.segments.forEach(function (w, i) {
            if (!isBraced(i)) { return; }
            parts.push(braceShape(px[i], TOP_INSET, px[i + 1] - px[i], size.h - TOP_INSET - BOT_INSET));
        });

        /* the selected bay, washed so the eye finds it (S32) */
        if (state.selected >= 0 && state.selected < sections()) {
            var si = state.selected;
            parts.push('<rect class="pc-sel" x="' + px[si] + '" y="0" width="' + (px[si + 1] - px[si]) + '" height="' + size.h + '"/>');
        }

        /* the boards */
        state.segments.forEach(function (w, i) {
            var bx = px[i] + INSET, bw = (px[i + 1] - px[i]) - INSET * 2;
            ys.forEach(function (y) { parts.push(shelfShape(bx, y, bw)); });
        });

        /* the uprights, over the board ends, then the pins that carry them */
        px.forEach(function (x) { parts.push(frameShape(x, size.h)); });
        px.forEach(function (x) {
            ys.forEach(function (y) { parts.push(pinShape(x, y + THICK / 2)); });
        });

        /* the floor the whole thing stands on */
        parts.push('<line class="pc-floor" x1="-4" y1="' + size.h + '" x2="' + (size.w + 4) + '" y2="' + size.h + '"/>');

        /* bay numbers and the overall dimension (S32, S34) */
        var fs = Math.max(6, size.h * 0.05);
        state.segments.forEach(function (w, i) {
            var mid = (px[i] + px[i + 1]) / 2;
            parts.push('<text class="pc-num' + (i === state.selected ? ' on' : '') + '" x="' + mid + '" y="' + (size.h + fs * 1.6) + '" font-size="' + fs + '">' + (i + 1) + '</text>');
        });
        parts.push('<line class="pc-dim" x1="0" y1="' + (size.h + fs * 2.7) + '" x2="' + size.w + '" y2="' + (size.h + fs * 2.7) + '"/>');
        parts.push('<text class="pc-dimtext" x="' + (size.w / 2) + '" y="' + (size.h + fs * 4.2) + '" font-size="' + fs + '">' + metres(totalLength()) + '</text>');

        svg.innerHTML = parts.join('');
        applyViewBox();
    }

    /* ---------- viewBox: fit, zoom, pan ------------------------------ */
    function baseView() {
        var size = contentSize();
        var box = $('[data-cfg-svg]').getBoundingClientRect();
        var ratio = (box.width && box.height) ? box.width / box.height : 2.2;
        var padX = Math.max(12, size.w * 0.02);
        var padY = Math.max(10, size.h * 0.05);
        /* room under the rack for the numbers and the dimension line */
        var needW = size.w + padX * 2;
        var needH = size.h * 1.38 + padY;
        /* grow whichever axis is short so the aspect matches the viewport and
           preserveAspectRatio has nothing left to letterbox */
        var w = needW, h = needH;
        if (w / h < ratio) { w = h * ratio; } else { h = w / ratio; }
        return { w: w, h: h, size: size, padX: padX };
    }

    function applyViewBox() {
        var base = baseView();
        var vw = base.w / state.zoom;
        var vh = base.h / state.zoom;
        var maxX = base.size.w + base.padX - vw;
        var minX = -base.padX;
        var cx = (state.panX === null) ? (base.size.w / 2) : state.panX;
        var vx = cx - vw / 2;
        if (maxX < minX) { vx = (base.size.w - vw) / 2; }         /* fits: centre it */
        else { vx = Math.min(maxX, Math.max(minX, vx)); }
        var vy = -base.h * 0.085 / state.zoom;
        $('[data-cfg-svg]').setAttribute('viewBox', vx + ' ' + vy + ' ' + vw + ' ' + vh);
        $('[data-cfg-scale]').textContent = Math.round(state.zoom * 100) + '%';
    }

    function fit() { state.zoom = 1; state.panX = null; applyViewBox(); }
    function zoomBy(f) {
        var before = state.zoom;
        state.zoom = Math.min(8, Math.max(1, state.zoom * f));
        if (state.panX === null && state.zoom !== before) { state.panX = contentSize().w / 2; }
        applyViewBox();
    }

    /* ===================================================================
     | THE PANEL
     =================================================================== */
    function renderPanel() {
        var q = bom();

        $('[data-cfg-f-height]').textContent   = state.height + ' cm';
        $('[data-cfg-f-depth]').textContent    = state.depth + ' cm';
        $('[data-cfg-f-length]').textContent   = metres(totalLength());
        $('[data-cfg-f-sections]').textContent = sections();
        $('[data-cfg-f-levels]').textContent   = state.levels;
        $('[data-cfg-f-shelves]').textContent  = shelves();

        $('[data-cfg-bom]').innerHTML = q.rows.map(function (r) {
            return '<li><span class="q">' + r.qty + '×</span>' +
                   '<span class="n">' + esc(r.label) + '</span>' +
                   '<span class="p">' + money(r.subtotal) + '</span></li>';
        }).join('');

        var rate = (LIMITS.vatRateBp / 100).toString().replace(/\.0+$/, '');
        $('[data-cfg-vatlabel]').textContent = LABELS.vat.replace(':rate', rate);
        $('[data-cfg-subtotal]').textContent = money(q.subtotal);
        $('[data-cfg-vat]').textContent      = money(q.vat);
        $('[data-cfg-total]').textContent    = money(q.total);

        var meta = $('[data-cfg-bar-meta]'), tot = $('[data-cfg-bar-total]');
        if (meta) { meta.textContent = sections() + ' · ' + metres(totalLength()); }
        if (tot)  { tot.textContent = money(q.total); }

        return q;
    }

    function renderChips() {
        var wrap = $('[data-cfg-chips]');
        var html = state.segments.map(function (w, i) {
            return '<button type="button" class="cfg-chip' + (isBraced(i) ? ' braced' : '') + '"' +
                   ' aria-pressed="' + (i === state.selected) + '" data-cfg-pick="' + i + '">' +
                   '<b>' + LABELS.section.replace(':n', i + 1) + '</b>' + w + ' cm</button>';
        }).join('');
        html += '<button type="button" class="cfg-chip cfg-chip-add" data-cfg-add>+ ' +
                @json(__('site.storefront.sankevi.cfg_add_section')) + '</button>';
        /*
         | The chips are the only way to pick or add a section, and rebuilding
         | them wholesale detached whatever the keyboard was on — activeElement
         | fell back to <body> and the next Tab restarted at the top of the
         | page. Put focus back where the person left it.
         */
        var active = document.activeElement;
        var hadFocus = wrap.contains(active)
            ? (active.dataset.cfgPick !== undefined ? 'pick:' + active.dataset.cfgPick : 'add')
            : null;

        wrap.innerHTML = html;

        if (hadFocus === 'add') {
            var addBtn = wrap.querySelector('[data-cfg-add]');
            if (addBtn) { addBtn.focus(); }
        } else if (hadFocus) {
            var idx = Math.min(parseInt(hadFocus.slice(5), 10), sections() - 1);
            var chip = wrap.querySelector('[data-cfg-pick="' + idx + '"]') || wrap.querySelector('[data-cfg-add]');
            if (chip) { chip.focus(); }
        }
    }

    function renderEditor() {
        var title = $('[data-cfg-editor-title]');
        var i = state.selected;
        title.textContent = LABELS.section.replace(':n', i + 1) + ' · ' + state.segments[i] + ' × ' + state.depth + ' cm';
        $('[data-cfg-editwidth]').value = state.segments[i];
        $('[data-cfg-left]').disabled   = (i === 0);
        $('[data-cfg-right]').disabled  = (i === sections() - 1);
        $('[data-cfg-delete]').disabled = (sections() === 1);
    }

    /**
     * Whether this configuration can be bought, and why not.
     *
     * A refusal is sticky: it stays on screen and the button stays disabled
     * until the next successful quote clears it. The old say() timeout hid the
     * warning after six seconds while the condition was still true.
     */
    function sellable(ok, message) {
        $$('[data-cfg-addcart]').forEach(function (b) { b.disabled = !ok; });
        var el = $('[data-cfg-alert]');
        if (ok) {
            if (el && el.dataset.sticky === '1') { el.hidden = true; el.dataset.sticky = '0'; }

            return;
        }
        /*
         | And blank the figures. A rack the yard cannot build has no price, and
         | leaving the mirror's number on screen — computed with a missing part
         | counted as zero — states one anyway, several hundred euros light.
         */
        ['[data-cfg-subtotal]', '[data-cfg-vat]', '[data-cfg-total]', '[data-cfg-bar-total]'].forEach(function (sel) {
            var n = $(sel);
            if (n) { n.textContent = '—'; }
        });
        say(message, true);
    }

    var alertTimer = null;
    function say(message, sticky) {
        var el = $('[data-cfg-alert]');
        if (!message) { el.hidden = true; return; }
        el.textContent = message;
        el.hidden = false;
        el.dataset.sticky = sticky ? '1' : '0';
        clearTimeout(alertTimer);
        if (!sticky) {
            alertTimer = setTimeout(function () { el.hidden = true; }, 6000);
        }
    }

    function render() {
        if (state.selected > sections() - 1) { state.selected = sections() - 1; }
        if (state.selected < 0) { state.selected = 0; }
        renderSvg();
        renderChips();
        renderEditor();
        var q = renderPanel();
        if (!q.priced) { sellable(false, LABELS.generic); }
        confirmWithServer();
    }

    /* ===================================================================
     | THE SERVER HAS THE LAST WORD
     |
     | The figures above are the browser's. This asks the server for the real
     | ones and overwrites them — so a mirror that has drifted corrects itself
     | within half a second rather than quietly under-quoting a rack.
     =================================================================== */
    var quoteTimer = null, quoteSeq = 0;
    function confirmWithServer() {
        clearTimeout(quoteTimer);
        quoteTimer = setTimeout(function () {
            var seq = ++quoteSeq;
            post('/configurator/quote', { config: payload() }).then(function (data) {
                if (seq !== quoteSeq) { return; }
                /*
                 | THE SERVER SAID NO. Staying silent here was the worst of the
                 | three: bom() prices a missing part at zero, so the panel
                 | shows a plausible, understated total; the server refuses to
                 | quote it; and the customer sees neither the refusal nor the
                 | real figure. Say it, and take the button away until the
                 | configuration is one this yard can actually build.
                 */
                if (!data || !data.ok) {
                    sellable(false, (data && data.reason) || LABELS.generic);
                    return;
                }
                sellable(true);
                $('[data-cfg-subtotal]').textContent = money(data.subtotal_cents);
                $('[data-cfg-vat]').textContent      = money(data.vat_cents);
                $('[data-cfg-total]').textContent    = money(data.total_cents);
                var tot = $('[data-cfg-bar-total]');
                if (tot) { tot.textContent = money(data.total_cents); }
                if (data.bom) {
                    $('[data-cfg-bom]').innerHTML = data.bom.map(function (r) {
                        return '<li><span class="q">' + r.quantity + '×</span>' +
                               '<span class="n">' + esc(r.label) + '</span>' +
                               '<span class="p">' + money(r.subtotal_cents) + '</span></li>';
                    }).join('');
                }
            }).catch(function () { /* the mirror stands in until the next change */ });
        }, 400);
    }

    function payload() {
        return { height: state.height, depth: state.depth, levels: state.levels, segments: state.segments };
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': BOOT.token
            },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function (r) { return r.json().then(function (j) { return Object.assign({ status: r.status }, j); }); });
    }

    /* ===================================================================
     | EVENTS
     =================================================================== */
    function wouldExceed(extra) {
        if (totalLength() + extra > LIMITS.maxLength) { say(LABELS.overLimit); return true; }
        return false;
    }

    $('[data-cfg-height]').addEventListener('change', function (e) { state.height = +e.target.value; state.code = null; render(); });
    $('[data-cfg-depth]').addEventListener('change',  function (e) { state.depth  = +e.target.value; state.code = null; render(); });
    $('[data-cfg-levels]').addEventListener('change', function (e) { state.levels = +e.target.value; state.code = null; render(); });

    $('[data-cfg-editwidth]').addEventListener('change', function (e) {
        var next = +e.target.value, current = state.segments[state.selected];
        if (wouldExceed(next - current)) { e.target.value = current; return; }
        state.segments[state.selected] = next;
        state.code = null;
        render();
    });

    root.addEventListener('click', function (e) {
        var pick = e.target.closest('[data-cfg-pick]');
        if (pick) { state.selected = +pick.dataset.cfgPick; render(); return; }

        if (e.target.closest('[data-cfg-add]')) {
            var w = +$('[data-cfg-newwidth]').value;
            if (wouldExceed(w)) { return; }
            state.segments.push(w);
            state.selected = sections() - 1;
            state.code = null;
            render();
            return;
        }
        if (e.target.closest('[data-cfg-delete]')) {
            if (sections() === 1) { return; }
            state.segments.splice(state.selected, 1);
            state.code = null;
            render();
            return;
        }
        if (e.target.closest('[data-cfg-left]'))  { move(-1); return; }
        if (e.target.closest('[data-cfg-right]')) { move(1);  return; }
        if (e.target.closest('[data-cfg-fit]'))     { fit(); return; }
        if (e.target.closest('[data-cfg-zoomin]'))  { zoomBy(1.35); return; }
        if (e.target.closest('[data-cfg-zoomout]')) { zoomBy(1 / 1.35); return; }
    });

    function move(dir) {
        var i = state.selected, j = i + dir;
        if (j < 0 || j >= sections()) { return; }
        var tmp = state.segments[i];
        state.segments[i] = state.segments[j];
        state.segments[j] = tmp;
        state.selected = j;
        state.code = null;
        render();
    }

    /* the phone's "see what's in the set" scrolls the docket into view */
    var see = document.querySelector('[data-cfg-see]');
    if (see) {
        see.addEventListener('click', function () {
            $('[data-cfg-bomwrap]').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    /* ---------- drag + wheel to pan; the canvas owns both ------------- */
    var svgEl = $('[data-cfg-svg]');
    var dragging = false, dragStartX = 0, dragStartPan = 0;

    svgEl.addEventListener('pointerdown', function (e) {
        dragging = true;
        dragStartX = e.clientX;
        dragStartPan = (state.panX === null) ? contentSize().w / 2 : state.panX;
        svgEl.classList.add('dragging');
        svgEl.setPointerCapture(e.pointerId);
    });
    svgEl.addEventListener('pointermove', function (e) {
        if (!dragging) { return; }
        var box = svgEl.getBoundingClientRect();
        var vw = baseView().w / state.zoom;
        state.panX = dragStartPan - ((e.clientX - dragStartX) / box.width) * vw;
        applyViewBox();
    });
    ['pointerup', 'pointercancel'].forEach(function (ev) {
        svgEl.addEventListener(ev, function () { dragging = false; svgEl.classList.remove('dragging'); });
    });
    svgEl.addEventListener('wheel', function (e) {
        /* Lenis drives the page; over the drawing the wheel belongs to us. */
        e.preventDefault();
        if (e.ctrlKey) { zoomBy(e.deltaY < 0 ? 1.1 : 1 / 1.1); return; }
        var vw = baseView().w / state.zoom;
        var box = svgEl.getBoundingClientRect();
        var delta = (Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY);
        state.panX = ((state.panX === null) ? contentSize().w / 2 : state.panX) + (delta / box.width) * vw;
        applyViewBox();
    }, { passive: false });

    window.addEventListener('resize', function () { applyViewBox(); });

    /* ---------- save, share, buy ------------------------------------- */
    function showCode(code, url) {
        state.code = code;
        $('[data-cfg-codeval]').textContent = code;
        $('[data-cfg-code]').hidden = false;
        if (url && window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '/configurator/' + code);
        }
    }

    function saveConfig() {
        return post('/configurator/save', { config: payload() }).then(function (data) {
            if (!data || !data.ok) { say((data && data.reason) || LABELS.generic); return null; }
            showCode(data.code, data.url);
            return data;
        });
    }

    $('[data-cfg-save]').addEventListener('click', function () {
        saveConfig().then(function (d) { if (d) { say(LABELS.saved); } });
    });

    $('[data-cfg-copy]').addEventListener('click', function () {
        saveConfig().then(function (d) {
            if (!d) { return; }
            var url = d.url;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(
                    function () { say(LABELS.copied); },
                    function () { window.prompt('', url); }
                );
            } else {
                window.prompt('', url);
            }
        });
    });

    $$('[data-cfg-addcart]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.disabled = true;
            post('/configurator/cart', { config: payload() }).then(function (data) {
                btn.disabled = false;
                if (!data || !data.ok) { say((data && data.reason) || LABELS.generic); return; }
                showCode(data.code, data.url);
                /* Reconcile against what the server actually banked, rather
                   than trusting the figure this page has been showing. */
                $('[data-cfg-total]').textContent = money(data.total_cents);
                var bar = $('[data-cfg-bar-total]');
                if (bar) { bar.textContent = money(data.total_cents); }
                say(data.message);
                if (window.Alpine && Alpine.store && Alpine.store('gvCart')) {
                    try { Alpine.store('gvCart').refresh(); } catch (err) {}
                }
                document.querySelectorAll('[data-cart-count]').forEach(function (n) {
                    n.textContent = data.item_count;
                });
            }).catch(function () { btn.disabled = false; say(LABELS.generic); });
        });
    });

    /* ---------- go ---------------------------------------------------- */
    if (state.code) { showCode(state.code, null); }
    render();
    requestAnimationFrame(fit);
})();
</script>
<style>
    /* The timber, as tokens — so daylight mode retunes the rack with the rest
       of the storefront instead of leaving a night-time photograph behind. */
    [data-cfg] { --pc-wood-l: #e2b57d; --pc-wood-m: #c2904f; --pc-wood-d: #8a5f2f; --pc-steel: #9aa79c; }
    html[data-mode="light"] [data-cfg] { --pc-wood-l: #edc79a; --pc-wood-m: #cd9a58; --pc-wood-d: #8f6531; --pc-steel: #7d8a80; }

    [data-cfg] .pc-post-body   { fill: url(#pcGrainV); }
    [data-cfg] .pc-post-lit    { fill: rgba(255, 236, 205, .30); }
    [data-cfg] .pc-post-shade  { fill: rgba(52, 30, 8, .40); }
    [data-cfg] .pc-hole        { fill: rgba(38, 21, 5, .55); }
    [data-cfg] .pc-foot        { fill: var(--pc-wood-d); }

    [data-cfg] .pc-board-body   { fill: url(#pcGrainH); }
    [data-cfg] .pc-board-lit    { fill: rgba(255, 240, 212, .34); }
    [data-cfg] .pc-board-shadow { fill: rgba(0, 0, 0, .30); }

    [data-cfg] .pc-pin        { fill: var(--accent); opacity: .9; }
    [data-cfg] .pc-brace line { stroke: var(--pc-steel); stroke-width: 1.6; stroke-linecap: round; opacity: .5; }
    [data-cfg] .pc-sel        { fill: var(--accent); opacity: .09; }
    [data-cfg] .pc-floor      { stroke: var(--line2); stroke-width: .5; }
    [data-cfg] .pc-num        { fill: var(--faint); text-anchor: middle; font-family: var(--display); }
    [data-cfg] .pc-num.on     { fill: var(--accent); }
    [data-cfg] .pc-dim        { stroke: var(--line2); stroke-width: .3; }
    [data-cfg] .pc-dimtext    { fill: var(--muted); text-anchor: middle; font-family: var(--display); }
</style>
@endpush

@endsection
