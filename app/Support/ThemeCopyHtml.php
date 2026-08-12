<?php

namespace App\Support;

/**
 * Sanitiser for the theme copy slots whose value is echoed UNESCAPED.
 *
 * A handful of headings across the themes carry inline markup — the accent
 * words in a headline are wrapped in <em>, and the Blade renders them with
 * {!! !!} so the wrapper survives. That is fine while the text is a constant in
 * a lang file. The moment a merchant can type into it, the field becomes a
 * stored-HTML sink: a shop admin who pastes a <script>, or whose account is
 * taken, gets it executed for every visitor to that storefront.
 *
 * So anything a merchant stores for an unescaped slot passes through here
 * first. The allowlist is deliberately tiny — the three tags that appear in the
 * shipped copy and nothing else. Everything else is stripped to its text, which
 * degrades to a plain heading rather than to a blank one.
 *
 * WHY NOT JUST ESCAPE THE OUTPUT: because then a merchant editing a heading
 * that ships with <em> in it would see the tags printed on their own homepage,
 * and the only way to avoid that would be to take the emphasis away from every
 * theme that uses it.
 */
class ThemeCopyHtml
{
    /**
     * Tags a merchant may keep. <em> carries the accent colour in the display
     * face (which has no italic — the accent is set in WEIGHT), <strong> is its
     * heavier sibling, and <br> is the only way to force a line break in a
     * headline that would otherwise wrap where the box decides.
     */
    private const ALLOWED = '<em><strong><br>';

    /** A slot is unescaped-by-convention when its name says so. */
    public static function isHtmlSlot(string $slot): bool
    {
        return str_ends_with($slot, '_html');
    }

    /**
     * Strip everything but the allowlist, and neutralise the attribute-borne
     * vectors that survive tag stripping.
     */
    public static function sanitize(string $value): string
    {
        // strip_tags keeps the allowlisted tags but preserves their ATTRIBUTES,
        // so <em onmouseover=...> would come through intact. Drop every
        // attribute by rewriting the surviving tags to their bare form.
        $clean = strip_tags($value, self::ALLOWED);

        $clean = preg_replace('/<\s*(em|strong)\b[^>]*>/i', '<$1>', $clean) ?? $clean;
        $clean = preg_replace('/<\s*br\b[^>]*\/?>/i', '<br>', $clean) ?? $clean;

        return trim($clean);
    }
}
