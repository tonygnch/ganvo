<?php

namespace App\Services\Rack;

use App\Models\RackPart;

/**
 * The active price book for one tenant, indexed for lookup.
 *
 * Also answers which sizes are actually buildable: the store settings say what
 * the configurator MAY offer, this says what it CAN price. A merchant who
 * deactivates every 300 cm frame should see 300 cm disappear from the picker,
 * not see a customer hit "no price for that combination" three clicks later.
 */
final class RackPriceBook
{
    /** @param array<string, RackPart> $parts keyed by RackPart::keyFor() */
    private function __construct(private readonly array $parts) {}

    public static function forTenant(int $tenantId): self
    {
        $parts = RackPart::query()
            ->forTenant($tenantId)
            ->active()
            ->get()
            ->keyBy(fn (RackPart $p) => $p->lookupKey())
            ->all();

        return new self($parts);
    }

    /** @param iterable<RackPart> $parts */
    public static function fromParts(iterable $parts): self
    {
        $indexed = [];
        foreach ($parts as $part) {
            $indexed[$part->lookupKey()] = $part;
        }

        return new self($indexed);
    }

    public function frame(int $heightCm, int $depthCm): RackPart
    {
        return $this->require(
            RackPart::keyFor(RackPart::KIND_FRAME, $heightCm, $depthCm),
            'cfg_err_no_frame_price'
        );
    }

    public function shelf(int $realWidthCm, int $realDepthCm): RackPart
    {
        return $this->board(RackPart::KIND_SHELF, $realWidthCm, $realDepthCm);
    }

    /**
     * A board by kind — shelf, wine tray or desk top — at its REAL size.
     *
     * The model boards refuse with their own message, so a customer who picks
     * the wine rack at a size nobody has priced a tray for is told that, not
     * that the yard has run out of shelves.
     */
    public function board(string $kind, int $realWidthCm, int $realDepthCm): RackPart
    {
        return $this->require(
            RackPart::keyFor($kind, null, $realDepthCm, $realWidthCm),
            $kind === RackPart::KIND_SHELF ? 'cfg_err_no_shelf_price' : 'cfg_err_no_model_price'
        );
    }

    public function flat(string $kind): RackPart
    {
        return $this->require($kind, 'cfg_err_no_part_price');
    }

    public function has(string $key): bool
    {
        return isset($this->parts[$key]);
    }

    /**
     * Narrow the offered dimension sets to what can actually be priced.
     * A height survives only if at least one depth pairs with it, and a depth
     * only if at least one height does — otherwise the picker offers a dead end.
     *
     * @param  array  $limits  Store::rackConfigurator()
     */
    public function narrow(array $limits): array
    {
        $heights = array_values(array_filter(
            $limits['heights'],
            fn (int $h) => (bool) array_filter($limits['depths'], fn (int $d) => $this->has(RackPart::keyFor(RackPart::KIND_FRAME, $h, $d)))
        ));

        $depths = array_values(array_filter(
            $limits['depths'],
            fn (int $d) => (bool) array_filter($heights, fn (int $h) => $this->has(RackPart::keyFor(RackPart::KIND_FRAME, $h, $d)))
        ));

        // A bay width is buildable only if its shelf exists at every surviving
        // depth — depth is chosen for the whole run, so a width that works at
        // 30 cm but not 60 cm would break the moment the customer changes depth.
        $widthTrim = $limits['shelf_width_trim_cm'];
        $depthTrim = $limits['shelf_depth_trim_cm'];
        $widths = array_values(array_filter(
            $limits['widths'],
            function (int $w) use ($depths, $widthTrim, $depthTrim) {
                foreach ($depths as $d) {
                    if (! $this->has(RackPart::keyFor(RackPart::KIND_SHELF, null, $d - $depthTrim, $w - $widthTrim))) {
                        return false;
                    }
                }

                return $depths !== [];
            }
        ));

        /*
         | The DEFAULT has to be a PAIR that exists, not a height and a depth
         | narrowed separately.
         |
         | Store::rackConfigurator() picks a default height and depth from the
         | sizes the merchant OFFERS. Deactivate the frame at that exact
         | combination — one toggle on the price book screen — and 210 still
         | survives (it pairs with 30/40/50) and 60 still survives (it pairs
         | with 150/180/240/300), yet 210 x 60 itself cannot be built. Picking
         | the two independently reproduces the dead pair and the page opens on
         | a configuration nothing can price.
         |
         | So: keep the merchant's preferred pair when it is buildable, then
         | keep as much of it as possible — their height with another depth,
         | their depth with another height — and only then fall to whatever the
         | price book still has.
         */
        [$defH, $defD] = $this->firstBuildablePair(
            $heights,
            $depths,
            $limits['default_height_cm'],
            $limits['default_depth_cm']
        );

        $widthDefault = in_array($limits['default_width_cm'], $widths, true)
            ? $limits['default_width_cm']
            : (int) ($widths[0] ?? 0);

        /*
         | A MODEL IS OFFERED ONLY WHEN ITS BOARDS ARE PRICED.
         |
         | The same rule as a bay width above, for the same reason: its board
         | must exist at every surviving width and depth, or picking the model
         | leads somewhere that cannot be quoted. Nothing is pre-priced, so the
         | wine and office racks appear in the picker the day the merchant fills
         | in their boards — and not a day before, with a made-up price on them.
         */
        $types = array_values(array_filter(
            $limits['types'] ?? [RackConfig::TYPE_SINGLE],
            function (string $type) use ($widths, $depths, $widthTrim, $depthTrim) {
                $kind = RackConfig::modelBoardKind($type);
                if ($kind === null) {
                    return true;
                }
                if ($widths === [] || $depths === []) {
                    return false;
                }
                foreach ($widths as $w) {
                    foreach ($depths as $d) {
                        if (! $this->has(RackPart::keyFor($kind, null, $d - $depthTrim, $w - $widthTrim))) {
                            return false;
                        }
                    }
                }

                return true;
            }
        ));

        $typeDefault = in_array(RackConfig::TYPE_SINGLE, $types, true)
            ? RackConfig::TYPE_SINGLE
            : ($types[0] ?? RackConfig::TYPE_SINGLE);

        return array_replace($limits, [
            'heights' => $heights,
            'depths' => $depths,
            'widths' => $widths,
            'types' => $types,
            'default_height_cm' => $defH,
            'default_depth_cm' => $defD,
            'default_width_cm' => $widthDefault,
            'default_type' => $typeDefault,
        ]);
    }

    /**
     * @return array{0:int,1:int} the height/depth to open on, or [0, 0] when
     *                            no frame at all can be priced
     */
    private function firstBuildablePair(array $heights, array $depths, int $preferH, int $preferD): array
    {
        $exists = fn (int $h, int $d): bool => $this->has(RackPart::keyFor(RackPart::KIND_FRAME, $h, $d));

        if ($exists($preferH, $preferD)) {
            return [$preferH, $preferD];
        }

        foreach ($depths as $d) {
            if ($exists($preferH, $d)) {
                return [$preferH, $d];
            }
        }

        foreach ($heights as $h) {
            if ($exists($h, $preferD)) {
                return [$h, $preferD];
            }
        }

        foreach ($heights as $h) {
            foreach ($depths as $d) {
                if ($exists($h, $d)) {
                    return [$h, $d];
                }
            }
        }

        return [0, 0];
    }

    /** Whether a narrowed set of limits can build anything at all. */
    public static function canBuildAnything(array $narrowed): bool
    {
        return $narrowed['heights'] !== []
            && $narrowed['depths'] !== []
            && $narrowed['widths'] !== []
            // firstBuildablePair() returns 0 when no frame can be priced at all.
            && $narrowed['default_height_cm'] > 0
            && $narrowed['default_depth_cm'] > 0;
    }

    private function require(string $key, string $reasonKey): RackPart
    {
        if (! isset($this->parts[$key])) {
            throw new RackException($reasonKey);
        }

        return $this->parts[$key];
    }
}
