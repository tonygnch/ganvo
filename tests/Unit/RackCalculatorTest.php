<?php

namespace Tests\Unit;

use App\Models\RackPart;
use App\Services\Rack\RackCalculator;
use App\Services\Rack\RackConfig;
use App\Services\Rack\RackException;
use App\Services\Rack\RackPriceBook;
use PHPUnit\Framework\TestCase;

/**
 * The BOM engine, pinned against the client's own worked example (§6).
 *
 * This is the one part of the configurator that must not be wrong: a mistake
 * here does not throw, it quietly under-orders uprights for a two-tonne rack.
 * These tests build the price book in memory, so nothing touches a database.
 */
class RackCalculatorTest extends TestCase
{
    private const LIMITS = [
        'heights' => [150, 180, 210, 240, 300],
        'depths' => [30, 40, 50, 60],
        'widths' => [80, 100, 120],
        'levels' => [4, 5, 6, 7, 8, 9, 10],
        'shelf_width_trim_cm' => 3,
        'shelf_depth_trim_cm' => 1,
        'max_length_cm' => 2500,
        'vat_rate_bp' => 2000,
        'types' => ['single', 'office', 'wine'],
    ];

    /** The subset of Sankevi's seeded price book these tests exercise. */
    private function priceBook(): RackPriceBook
    {
        $parts = [];
        $add = function (array $attrs) use (&$parts) {
            $parts[] = new RackPart($attrs);
        };

        foreach ([150, 180, 210, 240, 300] as $h) {
            foreach ([30, 40, 50, 60] as $d) {
                $add(['kind' => 'frame', 'sku' => "FRAME-$h-$d", 'height_cm' => $h, 'depth_cm' => $d, 'price_cents' => 2355]);
            }
        }
        foreach ([77, 97, 117] as $w) {
            foreach ([29, 39, 49, 59] as $d) {
                $add(['kind' => 'shelf', 'sku' => "SHELF-$w-$d", 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => 2370]);
            }
        }
        $add(['kind' => 'end_pin', 'sku' => 'END-PIN-10', 'price_cents' => 38]);
        $add(['kind' => 'extension_pin', 'sku' => 'EXT-PIN-10', 'price_cents' => 36]);
        $add(['kind' => 'cross_brace', 'sku' => 'CROSS-BRACE', 'price_cents' => 710]);

        return RackPriceBook::fromParts($parts);
    }

    private function quote(RackConfig $config)
    {
        return (new RackCalculator)->quote($config, $this->priceBook(), self::LIMITS);
    }

    private function qtyOf($quote, string $kind, ?int $width = null): int
    {
        foreach ($quote->lines as $line) {
            if ($line['kind'] === $kind && ($width === null || $line['width_cm'] === $width)) {
                return $line['quantity'];
            }
        }

        return 0;
    }

    /**
     * §6 verbatim: 210 × 60, five 100 cm sections, four levels.
     * "6 × рамка 210×60, 20 × плот 97×59, 16 × КП, 32 × УП, 3 × кръстодържач"
     */
    public function test_the_clients_worked_example(): void
    {
        $config = RackConfig::of(210, 60, 4, [100, 100, 100, 100, 100]);
        $quote = $this->quote($config);

        $this->assertSame(6, $this->qtyOf($quote, 'frame'), 'frames');
        $this->assertSame(20, $this->qtyOf($quote, 'shelf', 97), 'shelves 97×59');
        $this->assertSame(16, $this->qtyOf($quote, 'end_pin'), 'end pins');
        $this->assertSame(32, $this->qtyOf($quote, 'extension_pin'), 'extension pins');
        $this->assertSame(3, $this->qtyOf($quote, 'cross_brace'), 'cross-braces');
        $this->assertSame(500, $quote->totalLengthCm(), 'total length cm');

        // The shelf is the real board, not the nominal bay.
        $shelf = collect($quote->lines)->firstWhere('kind', 'shelf');
        $this->assertSame(97, $shelf['width_cm']);
        $this->assertSame(59, $shelf['depth_cm']);
    }

    /**
     * The same example priced from the client's list:
     *   6×23.55 + 20×23.70 + 16×0.38 + 32×0.36 + 3×7.10 = €654.20
     */
    public function test_the_worked_example_totals(): void
    {
        $quote = $this->quote(RackConfig::of(210, 60, 4, [100, 100, 100, 100, 100]));

        $this->assertSame(65420, $quote->subtotalCents, 'subtotal ex-VAT');
        $this->assertSame(13084, $quote->vatCents, 'VAT at 20%');
        $this->assertSame(78504, $quote->totalCents, 'total incl VAT');
    }

    /** A single bay: two frames, no extension pins, one brace. */
    public function test_a_single_section(): void
    {
        $quote = $this->quote(RackConfig::of(210, 60, 4, [100]));

        $this->assertSame(2, $this->qtyOf($quote, 'frame'));
        $this->assertSame(4, $this->qtyOf($quote, 'shelf', 97));
        $this->assertSame(16, $this->qtyOf($quote, 'end_pin'));
        $this->assertSame(0, $this->qtyOf($quote, 'extension_pin'), 'no shared frames, so no extension pins');
        $this->assertSame(1, $this->qtyOf($quote, 'cross_brace'));

        // A zero-quantity line must not appear at all — it would read as an
        // item on the packing list that nobody is meant to pack.
        $this->assertSame(
            [],
            array_values(array_filter($quote->lines, fn ($l) => $l['kind'] === 'extension_pin'))
        );
    }

    /** §4's table, exactly. */
    public function test_cross_brace_every_other_bay(): void
    {
        foreach ([1 => 1, 2 => 1, 3 => 2, 4 => 2, 5 => 3, 6 => 3, 10 => 5, 20 => 10, 25 => 13] as $sections => $expected) {
            $config = RackConfig::of(210, 60, 4, array_fill(0, $sections, 100));
            $this->assertSame($expected, $config->crossBraceCount(), "$sections sections");
            $this->assertCount($expected, $config->bracedSectionIndexes());
        }
    }

    /** §7: mixed bay widths give mixed boards, and the lengths still add up. */
    public function test_mixed_section_widths(): void
    {
        $quote = $this->quote(RackConfig::of(210, 60, 4, [100, 100, 80, 120, 100]));

        $this->assertSame(500, $quote->totalLengthCm());
        $this->assertSame(12, $this->qtyOf($quote, 'shelf', 97), 'three 100 cm bays × 4 levels');
        $this->assertSame(4, $this->qtyOf($quote, 'shelf', 77), 'one 80 cm bay × 4 levels');
        $this->assertSame(4, $this->qtyOf($quote, 'shelf', 117), 'one 120 cm bay × 4 levels');
        // Frames and pins do not care how wide the bays are.
        $this->assertSame(6, $this->qtyOf($quote, 'frame'));
        $this->assertSame(32, $this->qtyOf($quote, 'extension_pin'));
    }

    /**
     * The property the whole pin scheme rests on: every shelf gets four
     * corners. End pins carry one shelf each, extension pins carry two.
     *
     * If someone "fixes" extensionPinCount() to 4·L·(S−1), this fails.
     */
    public function test_pins_supply_exactly_four_corners_per_shelf(): void
    {
        foreach ([1, 2, 3, 5, 9, 17, 25] as $sections) {
            foreach ([4, 6, 10] as $levels) {
                $config = RackConfig::of(210, 60, $levels, array_fill(0, $sections, 100));

                $corners = 4 * $config->shelfCount();
                $supplied = $config->endPinCount() + 2 * $config->extensionPinCount();

                $this->assertSame(
                    $corners,
                    $supplied,
                    "$sections sections × $levels levels: pins must supply exactly 4 corners per shelf"
                );
            }
        }
    }

    /** Frames are shared — N sections need N+1 uprights, never 2N. */
    public function test_frames_are_shared_between_sections(): void
    {
        foreach ([1, 2, 5, 25] as $sections) {
            $config = RackConfig::of(210, 60, 4, array_fill(0, $sections, 100));
            $this->assertSame($sections + 1, $config->frameCount());
        }
    }

    public function test_vat_is_applied_once_to_the_subtotal(): void
    {
        $quote = $this->quote(RackConfig::of(210, 60, 4, [100, 100, 100, 100, 100]));

        $this->assertSame(
            $quote->subtotalCents + $quote->vatCents,
            $quote->totalCents,
            'total must be exactly subtotal + VAT'
        );
        $this->assertSame(
            array_sum(array_column($quote->lines, 'subtotal_cents')),
            $quote->subtotalCents,
            'subtotal must be exactly the sum of the lines'
        );
    }

    public function test_zero_vat_rate_leaves_the_total_alone(): void
    {
        $limits = array_replace(self::LIMITS, ['vat_rate_bp' => 0]);
        $quote = (new RackCalculator)->quote(
            RackConfig::of(210, 60, 4, [100]),
            $this->priceBook(),
            $limits
        );

        $this->assertSame(0, $quote->vatCents);
        $this->assertSame($quote->subtotalCents, $quote->totalCents);
    }

    /* ---------------- validation: the forgeable surface ---------------- */

    public function test_rejects_a_height_that_is_not_offered(): void
    {
        $this->expectException(RackException::class);
        RackConfig::fromArray(['height' => 999, 'depth' => 60, 'levels' => 4, 'segments' => [100]], self::LIMITS);
    }

    public function test_rejects_a_section_width_that_is_not_offered(): void
    {
        $this->expectException(RackException::class);
        RackConfig::fromArray(['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100, 90]], self::LIMITS);
    }

    public function test_rejects_an_empty_rack(): void
    {
        $this->expectException(RackException::class);
        RackConfig::fromArray(['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => []], self::LIMITS);
    }

    /** §35: 25 metres is the online limit. */
    public function test_rejects_a_run_over_the_maximum_length(): void
    {
        // 26 × 100 cm = 2600 cm.
        $this->expectException(RackException::class);
        RackConfig::fromArray(
            ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => array_fill(0, 26, 100)],
            self::LIMITS
        );
    }

    public function test_accepts_a_run_exactly_at_the_maximum_length(): void
    {
        $config = RackConfig::fromArray(
            ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => array_fill(0, 25, 100)],
            self::LIMITS
        );

        $this->assertSame(2500, $config->totalLengthCm());
    }

    /** A payload with a hundred thousand bays must not be walked before it is refused. */
    public function test_rejects_an_absurd_section_count_cheaply(): void
    {
        $this->expectException(RackException::class);
        RackConfig::fromArray(
            ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => array_fill(0, 100000, 80)],
            self::LIMITS
        );
    }

    /** A price the browser sends is not a price — it is ignored. */
    public function test_a_price_in_the_payload_is_ignored(): void
    {
        $config = RackConfig::fromArray([
            'height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100],
            'total_cents' => 1, 'subtotal_cents' => 1, 'price' => 1,
        ], self::LIMITS);

        $quote = $this->quote($config);
        $this->assertGreaterThan(1, $quote->totalCents);
    }

    /* ------------- narrowing: what the picker may offer ------------- */

    private function bookWithout(array $skusOff): RackPriceBook
    {
        $parts = [];
        foreach ([150, 180, 210, 240, 300] as $h) {
            foreach ([30, 40, 50, 60] as $d) {
                $sku = "FRAME-$h-$d";
                if (in_array($sku, $skusOff, true)) {
                    continue;
                }
                $parts[] = new RackPart(['kind' => 'frame', 'sku' => $sku, 'height_cm' => $h, 'depth_cm' => $d, 'price_cents' => 2355]);
            }
        }
        foreach ([77, 97, 117] as $w) {
            foreach ([29, 39, 49, 59] as $d) {
                $parts[] = new RackPart(['kind' => 'shelf', 'sku' => "SHELF-$w-$d", 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => 2370]);
            }
        }
        $parts[] = new RackPart(['kind' => 'end_pin', 'price_cents' => 38]);
        $parts[] = new RackPart(['kind' => 'extension_pin', 'price_cents' => 36]);
        $parts[] = new RackPart(['kind' => 'cross_brace', 'price_cents' => 710]);

        return RackPriceBook::fromParts($parts);
    }

    private function limitsWithDefaults(): array
    {
        return self::LIMITS + [
            'default_height_cm' => 210,
            'default_depth_cm' => 60,
            'default_width_cm' => 100,
        ];
    }

    /**
     * THE ONE-TOGGLE OUTAGE.
     *
     * Retiring the frame at the store's own default size used to leave the
     * default untouched: 210 still survived (it pairs with 30/40/50) and 60
     * still survived (it pairs with 150/180/240/300), so both lists looked
     * healthy while the PAIR 210 x 60 could not be priced. The page then opened
     * on a configuration nothing could quote and 500'd for every visitor.
     */
    public function test_narrowing_moves_the_default_off_a_retired_frame(): void
    {
        $narrowed = $this->bookWithout(['FRAME-210-60'])->narrow($this->limitsWithDefaults());

        $this->assertTrue(RackPriceBook::canBuildAnything($narrowed));
        $this->assertNotSame(
            [210, 60],
            [$narrowed['default_height_cm'], $narrowed['default_depth_cm']],
            'the default must not stay on a pair that cannot be priced'
        );
        // It keeps as much of the merchant's preference as it can: the height.
        $this->assertSame(210, $narrowed['default_height_cm']);

        // And the default it lands on must actually quote.
        $config = RackConfig::of($narrowed['default_height_cm'], $narrowed['default_depth_cm'], 4, [$narrowed['default_width_cm']]);
        $quote = (new RackCalculator)->quote($config, $this->bookWithout(['FRAME-210-60']), $narrowed);
        $this->assertGreaterThan(0, $quote->totalCents);
    }

    /** Losing a whole height keeps the depth and moves the height instead. */
    public function test_narrowing_drops_a_height_with_no_depths_left(): void
    {
        $off = ['FRAME-210-30', 'FRAME-210-40', 'FRAME-210-50', 'FRAME-210-60'];
        $narrowed = $this->bookWithout($off)->narrow($this->limitsWithDefaults());

        $this->assertNotContains(210, $narrowed['heights'], 'a height nothing pairs with is not offered');
        $this->assertSame(60, $narrowed['default_depth_cm'], 'the merchant\'s depth survives');
        $this->assertContains($narrowed['default_height_cm'], $narrowed['heights']);
    }

    /** No frames at all: the configurator has nothing to sell and must say so. */
    public function test_narrowing_reports_a_price_book_that_can_build_nothing(): void
    {
        $allFrames = [];
        foreach ([150, 180, 210, 240, 300] as $h) {
            foreach ([30, 40, 50, 60] as $d) {
                $allFrames[] = "FRAME-$h-$d";
            }
        }

        $narrowed = $this->bookWithout($allFrames)->narrow($this->limitsWithDefaults());
        $this->assertFalse(RackPriceBook::canBuildAnything($narrowed));
    }

    /** An untouched price book must offer exactly what the merchant configured. */
    public function test_narrowing_changes_nothing_when_everything_is_priced(): void
    {
        $limits = $this->limitsWithDefaults();
        $narrowed = $this->bookWithout([])->narrow($limits);

        $this->assertSame($limits['heights'], $narrowed['heights']);
        $this->assertSame($limits['depths'], $narrowed['depths']);
        $this->assertSame($limits['widths'], $narrowed['widths']);
        $this->assertSame(210, $narrowed['default_height_cm']);
        $this->assertSame(60, $narrowed['default_depth_cm']);
    }

    public function test_missing_price_book_entry_is_refused_not_priced_at_zero(): void
    {
        $book = RackPriceBook::fromParts([
            new RackPart(['kind' => 'frame', 'height_cm' => 210, 'depth_cm' => 60, 'price_cents' => 2355]),
        ]);

        $this->expectException(RackException::class);
        (new RackCalculator)->quote(RackConfig::of(210, 60, 4, [100]), $book, self::LIMITS);
    }

    /* ---- the office and wine models ------------------------------------ */

    /** The same price book, plus a wine tray and a desk top at every board size. */
    private function modelPriceBook(): RackPriceBook
    {
        $parts = [];
        foreach ([150, 180, 210, 240, 300] as $h) {
            foreach ([30, 40, 50, 60] as $d) {
                $parts[] = new RackPart(['kind' => 'frame', 'height_cm' => $h, 'depth_cm' => $d, 'price_cents' => 2355]);
            }
        }
        foreach ([77, 97, 117] as $w) {
            foreach ([29, 39, 49, 59] as $d) {
                $parts[] = new RackPart(['kind' => 'shelf', 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => 2370]);
                $parts[] = new RackPart(['kind' => 'wine_tray', 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => 3100]);
            }
        }
        $parts[] = new RackPart(['kind' => 'end_pin', 'price_cents' => 38]);
        $parts[] = new RackPart(['kind' => 'extension_pin', 'price_cents' => 36]);
        $parts[] = new RackPart(['kind' => 'cross_brace', 'price_cents' => 710]);

        return RackPriceBook::fromParts($parts);
    }

    private function narrowLimits(): array
    {
        return self::LIMITS + ['default_height_cm' => 210, 'default_depth_cm' => 60, 'default_width_cm' => 100];
    }

    public function test_a_wine_rack_swaps_every_shelf_for_a_tray(): void
    {
        $quote = (new RackCalculator)->quote(RackConfig::of(210, 60, 4, [100, 100], 'wine'), $this->modelPriceBook(), self::LIMITS);

        $this->assertSame(0, $this->qtyOf($quote, 'shelf'), 'a wine rack has no flat shelves');
        $this->assertSame(8, $this->qtyOf($quote, 'wine_tray', 97), 'one tray per level per section');
        $this->assertSame(3, $this->qtyOf($quote, 'frame'), 'the frames do not change');
        $this->assertSame(16, $this->qtyOf($quote, 'end_pin'));
        $this->assertSame(8, $this->qtyOf($quote, 'extension_pin'));
        $this->assertSame(1, $this->qtyOf($quote, 'cross_brace'));
    }

    public function test_an_office_rack_gives_one_level_of_each_section_to_a_deeper_desk_shelf(): void
    {
        // a 40 cm deep rack with a 60 cm desk plate
        $quote = (new RackCalculator)->quote(RackConfig::of(210, 40, 4, [100, 120], 'office', 60), $this->modelPriceBook(), self::LIMITS);
        $lines = collect($quote->lines);

        $this->assertSame(3, $this->qtyOf($quote, 'shelf', 97), 'three shelves over the 100 cm desk');
        $this->assertSame(3, $this->qtyOf($quote, 'shelf', 117), 'three shelves over the 120 cm desk');
        $this->assertSame(39, $lines->firstWhere('kind', 'shelf')['depth_cm'], 'the shelves are the rack\'s own depth');

        $desk = $lines->where('kind', 'desk_top')->values();
        $this->assertCount(2, $desk);
        $this->assertSame([1, 1], $desk->pluck('quantity')->all());
        $this->assertSame([59, 59], $desk->pluck('depth_cm')->all(), 'the desk is the deeper plate the customer chose');
        $this->assertSame(2370, $desk[0]['unit_price_cents'], 'priced as the shelf of that size — there is no desk price table');

        $this->assertSame(16, $this->qtyOf($quote, 'end_pin'), 'the desk sits on pins like any board');
    }

    public function test_an_office_desk_defaults_to_the_deepest_plate_and_is_never_shallower_than_the_rack(): void
    {
        $config = RackConfig::fromArray(['type' => 'office', 'height' => 210, 'depth' => 40, 'levels' => 4, 'segments' => [100]], self::LIMITS);
        $this->assertSame(60, $config->deskDepthCm);

        $this->expectException(RackException::class);
        RackConfig::fromArray(['type' => 'office', 'height' => 210, 'depth' => 50, 'levels' => 4, 'segments' => [100], 'desk_depth' => 40], self::LIMITS);
    }

    public function test_every_model_still_supplies_four_pin_corners_per_board(): void
    {
        foreach (['single', 'office', 'wine'] as $type) {
            foreach ([1, 3, 6] as $sections) {
                $config = RackConfig::of(210, 60, 5, array_fill(0, $sections, 100), $type);
                $corners = $config->endPinCount() + 2 * $config->extensionPinCount();
                $this->assertSame(4 * $config->boardCount(), $corners, "$type, $sections sections");
            }
        }
    }

    public function test_a_model_whose_boards_are_not_priced_is_refused(): void
    {
        $this->expectException(RackException::class);
        // the original price book has shelves, and no wine trays at all
        (new RackCalculator)->quote(RackConfig::of(210, 60, 4, [100], 'wine'), $this->priceBook(), self::LIMITS);
    }

    public function test_the_wine_rack_is_offered_only_once_its_trays_are_priced_and_the_office_rack_always(): void
    {
        // the office desk is a shelf, so shelves are all the office rack needs
        $this->assertSame(['single', 'office'], $this->priceBook()->narrow($this->narrowLimits())['types']);
        $this->assertSame(['single', 'office', 'wine'], $this->modelPriceBook()->narrow($this->narrowLimits())['types']);
    }

    public function test_rejects_a_model_that_is_not_offered(): void
    {
        $this->expectException(RackException::class);
        RackConfig::fromArray(
            ['type' => 'wine', 'height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100]],
            ['types' => ['single']] + self::LIMITS
        );
    }

    public function test_the_office_desk_is_only_on_the_sections_chosen(): void
    {
        $config = RackConfig::fromArray(
            ['type' => 'office', 'height' => 210, 'depth' => 40, 'desk_depth' => 60, 'levels' => 4, 'segments' => [100, 100, 100], 'desk_sections' => [1]],
            self::LIMITS
        );
        $quote = $this->quote($config);

        $this->assertSame([1], $config->deskSections);
        $this->assertSame(1, $this->qtyOf($quote, 'desk_top'));
        $this->assertSame(4 + 3 + 4, $this->qtyOf($quote, 'shelf'), 'the sections without the desk have a shelf at that level');
        // every board corner still has a pin: an end pin serves one, a double-sided extension pin two
        $this->assertSame(4 * $config->boardCount(), $config->endPinCount() + 2 * $config->extensionPinCount(), 'the pins do not change');
    }

    public function test_an_office_rack_left_without_desk_sections_has_the_desk_everywhere_and_an_empty_choice_is_refused(): void
    {
        $rack = ['type' => 'office', 'height' => 210, 'depth' => 40, 'levels' => 4, 'segments' => [100, 100]];

        $this->assertSame([0, 1], RackConfig::fromArray($rack, self::LIMITS)->deskSections);
        $this->assertSame([0, 1], RackConfig::of(210, 40, 4, [100, 100], 'office', 60)->deskSections, 'an office rack saved before the choice existed');
        $this->assertSame([], RackConfig::fromArray(['type' => 'single'] + $rack, self::LIMITS)->deskSections);

        foreach ([[], [2], 'x'] as $bad) {
            try {
                RackConfig::fromArray($rack + ['desk_sections' => $bad], self::LIMITS);
                $this->fail('accepted desk_sections '.json_encode($bad));
            } catch (RackException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_a_brace_added_by_hand_is_counted_and_a_standard_one_is_not_doubled(): void
    {
        // four sections: 1 and 3 are braced as standard (indexes 0 and 2)
        $config = RackConfig::fromArray(
            ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100, 100, 100, 100], 'extra_braces' => [1, 2]],
            self::LIMITS
        );

        $this->assertSame([1], $config->extraBraces, 'section 3 carries a brace anyway; that is not a second one');
        $this->assertSame([0, 1, 2], $config->bracedSectionIndexes());
        $this->assertSame(3, $this->qtyOf($this->quote($config), 'cross_brace'));
    }

    public function test_a_brace_on_a_section_that_does_not_exist_is_refused(): void
    {
        $this->expectException(RackException::class);
        RackConfig::fromArray(
            ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100, 100], 'extra_braces' => [5]],
            self::LIMITS
        );
    }

    public function test_an_unknown_model_read_back_from_storage_is_the_plain_rack(): void
    {
        $this->assertSame('single', RackConfig::of(210, 60, 4, [100], 'garden-shed')->type);
    }
}
