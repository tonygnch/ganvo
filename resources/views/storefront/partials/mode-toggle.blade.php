{{--
 | Visitor light/dark mode toggle. Renders only when the active theme's
 | manifest declares an alternate mode ('modes' section) — the theme's own
 | :root is its native mode; <html data-mode="…"> applies the alternate
 | (CSS emitted by ThemeCustomizer::headExtras()).
 |
 | Include in the theme header's utility cluster:
 |   @include('storefront.partials.mode-toggle')
 |
 | The choice persists per visitor (localStorage) and is applied by the
 | mode-boot partial before first paint, so there is no flash.
--}}
@if ($alt = $theme->alternateMode())
    <button type="button" class="gv-mode" data-gv-mode-alt="{{ $alt }}"
            aria-label="{{ __('site.storefront.mode_toggle') }}" aria-pressed="false">
        {{-- sun (shown when the alternate would brighten / is active-dark logic in CSS) --}}
        <svg class="gv-sun" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M19.4 4.6l-1.8 1.8M6.4 17.6l-1.8 1.8"/>
        </svg>
        {{-- moon --}}
        <svg class="gv-moon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.6 6.6 0 0 0 9.8 9.8Z"/>
        </svg>
    </button>
@endif
