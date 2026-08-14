<?php

namespace App\Support;

/**
 * Reads a length out of the free text a merchant types into an option value.
 *
 * Products sold by the square metre are described by their sizes, and those
 * sizes are already written on the axes — „0,29 м", „196 мм". Asking the
 * merchant to restate them as numbers in a second set of fields would be asking
 * them to type the same thing twice and keep the two copies agreeing.
 *
 * So this parses what is already there. Everything comes back in METRES,
 * because that is the unit the prices are quoted in, and one catalogue mixes
 * „0,29 м" with „196 мм" across different products.
 *
 * IT RETURNS NULL RATHER THAN GUESSING. A value it cannot read is not worth a
 * silent zero or an assumed unit — the caller shows the merchant which value
 * failed and leaves that price alone, which is recoverable. A wrong price that
 * looks deliberate is not.
 */
class Dimension
{
    /** Multiplier to metres for each suffix we accept, longest first. */
    private const UNITS = [
        'мм' => 0.001,
        'mm' => 0.001,
        'см' => 0.01,
        'cm' => 0.01,
        'м' => 1.0,
        'm' => 1.0,
    ];

    /**
     * The length in metres, or null when the text carries no readable number.
     *
     * Accepts a comma or a dot as the decimal separator — Bulgarian writes
     * „0,29" — and tolerates the label text merchants put in front of the
     * number, e.g. „ш. 0,29 м".
     */
    public static function toMetres(string $value): ?float
    {
        $text = trim(mb_strtolower($value));

        if ($text === '') {
            return null;
        }

        // The LAST number in the string: „ш. 0,29 м" has a stray „ш." in front,
        // and a leading-number match would be fooled by any prefix carrying
        // digits.
        if (! preg_match_all('/(\d+(?:[.,]\d+)?)/u', $text, $matches)) {
            return null;
        }

        $number = (float) str_replace(',', '.', end($matches[1]));

        if ($number <= 0.0) {
            return null;
        }

        // The suffix after that number decides the scale. No suffix means
        // metres, which is what a bare number in this catalogue means.
        $tail = mb_substr($text, mb_strpos($text, end($matches[1])) + mb_strlen(end($matches[1])));
        $tail = trim(str_replace(['.', ' '], '', $tail));

        foreach (self::UNITS as $suffix => $factor) {
            if ($tail === $suffix) {
                return $number * $factor;
            }
        }

        return $tail === '' ? $number : null;
    }
}
