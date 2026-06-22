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
 * Each shade is rendered at a FIXED target lightness (a Tailwind-style
 * ramp), keeping the picked color's hue + saturation. This is what makes
 * the palette robust for any input: an earlier version mixed the base
 * toward white/black, which collapsed at the extremes — a pure-black accent
 * produced an identical `0 0 0` for shades 500–950, so Filament's selected /
 * toggle / checked states lost all contrast and the merchant "couldn't see
 * what was enabled." Anchoring lightness per shade guarantees every shade is
 * visually distinct (black simply yields a usable monochrome grey ramp),
 * and that shade 600 (button fill) is always dark enough for white text
 * regardless of how light the picked color is.
 */
class AccentPalette
{
    /**
     * Shade => target lightness (0..1). A perceptually even ramp from a
     * near-white tint (50) to a near-black (950), matching how Tailwind /
     * Radix scales space their steps.
     */
    private const LIGHTNESS = [
        50  => 0.97,
        100 => 0.93,
        200 => 0.86,
        300 => 0.76,
        400 => 0.64,
        500 => 0.53,
        600 => 0.45,
        700 => 0.37,
        800 => 0.29,
        900 => 0.22,
        950 => 0.15,
    ];

    /** Emerald-500, mirrors the panel's default primary. */
    private const FALLBACK = [16, 185, 129];

    /**
     * @return array<int, string> shade => "r g b"
     */
    public static function shades(string $hex): array
    {
        [$h, $s] = self::rgbToHsl(...self::parse($hex));

        $out = [];
        foreach (self::LIGHTNESS as $shade => $l) {
            [$r, $g, $b] = self::hslToRgb($h, $s, $l);
            $out[$shade] = "{$r} {$g} {$b}";
        }

        return $out;
    }

    /**
     * A `<style>`-ready CSS rule overriding Filament's primary palette.
     *
     * Filament v5 sets each shade as a CSS variable holding a *complete*
     * color value, e.g. `--primary-600: oklch(0.596 0.145 163.2)`, then
     * consumes it via `rgb(var(--primary-600) / <alpha>)`-equivalent maps.
     * We therefore emit a full `rgb(r g b)` value (NOT a bare `r g b`
     * triplet — an earlier version did that, which is not a valid color and
     * was silently dropped, so the accent never applied). `rgb(r g b)` is a
     * valid <color> in every modern browser and composes correctly wherever
     * Filament references the variable.
     */
    public static function css(string $hex, string $selector = ':root, :host'): string
    {
        $vars = '';
        foreach (self::shades($hex) as $shade => $rgb) {
            $vars .= "--primary-{$shade}: rgb({$rgb});";
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

    /**
     * Whether a color is a *usable* admin accent. Near-black, near-white,
     * and very desaturated (grey) inputs can't drive a legible primary
     * palette — Filament needs a saturated mid-tone so its selected /
     * toggle-on / focus states read clearly. Callers treat an unusable
     * accent as "unset" and fall back to the panel's default.
     *
     * Thresholds operate on HSL: reject L outside [0.18, 0.82] (too dark /
     * too light to tell shades apart) or S below 0.20 (greyscale).
     */
    public static function isUsable(?string $hex): bool
    {
        if (! self::isValid($hex)) {
            return false;
        }

        [$h, $s, $l] = self::rgbToHsl(...self::parse((string) $hex));

        return $s >= 0.20 && $l >= 0.18 && $l <= 0.82;
    }

    /**
     * RGB (0–255) → HSL. Hue in degrees (0–360), S + L in 0–1.
     *
     * @return array{0:float,1:float,2:float} [h, s, l]
     */
    private static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d == 0.0) {
            return [0.0, 0.0, $l]; // achromatic (grey/black/white)
        }

        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        if ($max === $r) {
            $h = (($g - $b) / $d) + ($g < $b ? 6 : 0);
        } elseif ($max === $g) {
            $h = (($b - $r) / $d) + 2;
        } else {
            $h = (($r - $g) / $d) + 4;
        }

        return [$h * 60, $s, $l];
    }

    /**
     * HSL → RGB (0–255). Hue in degrees, S + L in 0–1.
     *
     * @return array{0:int,1:int,2:int}
     */
    private static function hslToRgb(float $h, float $s, float $l): array
    {
        if ($s == 0.0) {
            $v = (int) round($l * 255);
            return [$v, $v, $v];
        }

        $h /= 360;
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        return [
            (int) round(self::hue2rgb($p, $q, $h + 1 / 3) * 255),
            (int) round(self::hue2rgb($p, $q, $h) * 255),
            (int) round(self::hue2rgb($p, $q, $h - 1 / 3) * 255),
        ];
    }

    private static function hue2rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }
}
