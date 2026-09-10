<?php

namespace App\Services\Rack;

use App\Models\RackPart;

/**
 * Names for the things in a quote.
 *
 * Kept apart from the calculator so the arithmetic stays free of locale, and
 * so a BOM frozen onto an order keeps the wording it was ordered in even after
 * the translations change.
 */
final class RackPresenter
{
    /** "Рамка 210×60 cm" / "Плот 97×59 cm" / "Кръстодържач" */
    public static function lineLabel(array $line): string
    {
        return match ($line['kind']) {
            RackPart::KIND_FRAME => __('site.storefront.sankevi.cfg_part_frame', [
                'h' => $line['height_cm'],
                'd' => $line['depth_cm'],
            ]),
            RackPart::KIND_SHELF => __('site.storefront.sankevi.cfg_part_shelf', [
                'w' => $line['width_cm'],
                'd' => $line['depth_cm'],
            ]),
            RackPart::KIND_END_PIN => __('site.storefront.sankevi.cfg_part_end_pin'),
            RackPart::KIND_EXTENSION_PIN => __('site.storefront.sankevi.cfg_part_extension_pin'),
            RackPart::KIND_CROSS_BRACE => __('site.storefront.sankevi.cfg_part_cross_brace'),
            default => (string) ($line['sku'] ?? $line['kind']),
        };
    }

    /** The one-line name a configured rack carries into the cart and the order. */
    public static function rackName(RackConfig $config): string
    {
        return __('site.storefront.sankevi.cfg_rack_name', [
            'h' => $config->heightCm,
            'd' => $config->depthCm,
            'sections' => self::sectionsLabel($config->sectionCount()),
            'levels' => self::levelsLabel($config->levels),
            'metres' => number_format($config->totalLengthCm() / 100, 2),
        ]);
    }

    /** "5 секции" / "1 секция" — Bulgarian needs the count agreed with the noun. */
    public static function sectionsLabel(int $count): string
    {
        return trans_choice('site.storefront.sankevi.cfg_sections_count', $count, ['count' => $count]);
    }

    public static function levelsLabel(int $count): string
    {
        return trans_choice('site.storefront.sankevi.cfg_levels_count', $count, ['count' => $count]);
    }

    /** The BOM with labels folded in, ready to render or to freeze. */
    public static function labelledLines(RackQuote $quote): array
    {
        return array_map(
            fn (array $line) => $line + ['label' => self::lineLabel($line)],
            $quote->lines
        );
    }
}
