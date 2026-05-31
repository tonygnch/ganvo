<?php

namespace App\Support;

/**
 * Generates a Filament-compatible primary-color palette (shades 50–950)
 * from a single accent hex, emitting space-separated RGB triplets.
 *
 * Filament v4 (RGB color mode) consumes its theme colors as
 * `rgb(var(--primary-600) / <alpha>)`, so overriding `--primary-50…950`
 * at :root — injected after Filament's own stylesheet — re-tints the
 * entire admin panel to the merchant's accent without recompiling CSS.
 *
 * The ramp lightens the base toward white for 50–400 and darkens toward
 * black for 600–950, with the base sitting at 500. It's a perceptually
 * reasonable accent ramp — not a designed palette, but right for a
 * single-color "tint my admin" control.
 */
class AccentPalette
{
    /**
     * Shade => mix factor. Negative = fraction toward white (lighter),
     * positive = fraction toward black (darker), 0 = the base color.
     */
    private const RAMP = [
        50  => -0.95,
        100 => -0.90,
        200 => -0.75,
        300 => -0.58,
        400 => -0.30,
        500 => 0.0,
        600 => 0.12,
        700 => 0.28,
        800 => 0.44,
        900 => 0.58,
        950 => 0.72,
    ];

    /** Emerald-500, mirrors the panel's default primary. */
    private const FALLBACK = [16, 185, 129];

    /**
     * @return array<int, string> shade => "r g b"
     */
    public static function shades(string $hex): array
    {
        [$r, $g, $b] = self::parse($hex);

        $out = [];
        foreach (self::RAMP as $shade => $factor) {
            if ($factor < 0) {
                $t = -$factor;
                $rr = (int) round($r + (255 - $r) * $t);
                $gg = (int) round($g + (255 - $g) * $t);
                $bb = (int) round($b + (255 - $b) * $t);
            } else {
                $t = $factor;
                $rr = (int) round($r * (1 - $t));
                $gg = (int) round($g * (1 - $t));
                $bb = (int) round($b * (1 - $t));
            }
            $out[$shade] = "{$rr} {$gg} {$bb}";
        }

        return $out;
    }

    /**
     * A `<style>`-ready CSS rule overriding the primary palette variables.
     */
    public static function css(string $hex, string $selector = ':root, :host'): string
    {
        $vars = '';
        foreach (self::shades($hex) as $shade => $rgb) {
            $vars .= "--primary-{$shade}: {$rgb};";
        }

        return "{$selector}{{$vars}}";
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    public static function parse(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return self::FALLBACK;
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function isValid(?string $hex): bool
    {
        if (! is_string($hex)) {
            return false;
        }

        $h = ltrim(trim($hex), '#');

        return (strlen($h) === 3 || strlen($h) === 6) && ctype_xdigit($h);
    }
}
