<?php

namespace App\Services\Rack;

use App\Models\RackPart;

/**
 * The active price book for one tenant, indexed for lookup.
 *
 * Also answers which sizes are actually buildable: the store settings say what
 * the configurator MAY offer, this says what it CAN price. A merchant who
 * leaves every 300 cm frame blank should see 300 cm disappear from the picker,
 * not see a customer hit "no price for that combination" three clicks later.
 *
 * Prices are per rack type — the plain, office and wine racks each have their
 * own frames and boards — so that answer is per type too: each type is sold in
 * the sizes its own tables price. The fasteners are shared.
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

    public function frame(int $heightCm, int $depthCm, string $type = RackConfig::TYPE_SINGLE): RackPart
    {
        // As with the boards: a model nobody has priced says so, rather than
        // blaming whichever of its parts happened to be looked up first.
        return $this->require(
            RackPart::keyFor(RackPart::KIND_FRAME, $heightCm, $depthCm, null, $type),
            $type === RackConfig::TYPE_SINGLE ? 'cfg_err_no_frame_price' : 'cfg_err_no_model_price'
        );
    }

    public function shelf(int $realWidthCm, int $realDepthCm, string $type = RackConfig::TYPE_SINGLE): RackPart
    {
        return $this->board(RackPart::KIND_SHELF, $realWidthCm, $realDepthCm, $type);
    }

    /**
     * A board by kind — shelf or wine tray — at its REAL size, for a rack type.
     *
     * The model boards refuse with their own message, so a customer who picks
     * the wine rack at a size nobody has priced a tray for is told that, not
     * that the yard has run out of shelves.
     */
    public function board(string $kind, int $realWidthCm, int $realDepthCm, string $type = RackConfig::TYPE_SINGLE): RackPart
    {
        return $this->require(
            RackPart::keyFor($kind, null, $realDepthCm, $realWidthCm, $type),
            $kind === RackPart::KIND_SHELF && $type === RackConfig::TYPE_SINGLE ? 'cfg_err_no_shelf_price' : 'cfg_err_no_model_price'
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
     * Narrow the offered dimension sets to what can actually be priced, per
     * rack type.
     *
     * A type is offered only when its own tables can build something: a
     * height that pairs with at least one depth, and a bay width whose board
     * exists at every one of those depths. The result keeps the familiar
     * top-level lists — every size SOME type sells, which is what the pickers
     * list — and adds `by_type`, the sizes and defaults of each offered type,
     * which is what a configuration is checked against (RackConfig::fromArray).
     *
     * @param  array  $limits  Store::rackConfigurator()
     */
    public function narrow(array $limits): array
    {
        $byType = [];
        foreach ($limits['types'] ?? [RackConfig::TYPE_SINGLE] as $type) {
            $sizes = $this->sizesFor($type, $limits);
            if ($sizes !== null) {
                $byType[$type] = $sizes;
            }
        }

        $types = array_keys($byType);
        $typeDefault = isset($byType[RackConfig::TYPE_SINGLE])
            ? RackConfig::TYPE_SINGLE
            : ($types[0] ?? RackConfig::TYPE_SINGLE);
        $default = $byType[$typeDefault] ?? [
            'heights' => [], 'depths' => [], 'widths' => [],
            'default_height_cm' => 0, 'default_depth_cm' => 0, 'default_width_cm' => 0,
        ];

        $union = function (string $key) use ($byType): array {
            $all = [];
            foreach ($byType as $sizes) {
                foreach ($sizes[$key] as $v) {
                    $all[$v] = $v;
                }
            }
            $all = array_values($all);
            sort($all);

            return $all;
        };
        $depths = $union('depths');

        return array_replace($limits, [
            'heights' => $union('heights'),
            'depths' => $depths,
            'widths' => $union('widths'),
            'types' => $types,
            'by_type' => $byType,
            'default_height_cm' => $default['default_height_cm'],
            'default_depth_cm' => $default['default_depth_cm'],
            'default_width_cm' => $default['default_width_cm'],
            'default_type' => $typeDefault,
            // a depth that is no longer sold falls back to the rack's own default
            'model_depth_cm' => in_array($limits['model_depth_cm'] ?? null, $depths, true) ? $limits['model_depth_cm'] : $default['default_depth_cm'],
        ]);
    }

    /**
     * What one rack type is sold in, or null when its tables cannot build anything.
     *
     * @return ?array{heights:list<int>,depths:list<int>,widths:list<int>,default_height_cm:int,default_depth_cm:int,default_width_cm:int}
     */
    private function sizesFor(string $type, array $limits): ?array
    {
        $frame = fn (int $h, int $d): bool => $this->has(RackPart::keyFor(RackPart::KIND_FRAME, $h, $d, null, $type));
        $boardKind = RackConfig::modelBoardKind($type) ?? RackPart::KIND_SHELF;

        // A height survives only if at least one depth pairs with it, and a
        // depth only if at least one height does — otherwise the picker offers
        // a dead end.
        $heights = array_values(array_filter(
            $limits['heights'],
            fn (int $h) => (bool) array_filter($limits['depths'], fn (int $d) => $frame($h, $d))
        ));

        $depths = array_values(array_filter(
            $limits['depths'],
            fn (int $d) => (bool) array_filter($heights, fn (int $h) => $frame($h, $d))
        ));

        // A bay width is buildable only if its board exists at every surviving
        // depth — depth is chosen for the whole run, so a width that works at
        // 30 cm but not 60 cm would break the moment the customer changes depth.
        $widthTrim = $limits['shelf_width_trim_cm'];
        $depthTrim = $limits['shelf_depth_trim_cm'];
        $widths = array_values(array_filter(
            $limits['widths'],
            function (int $w) use ($depths, $widthTrim, $depthTrim, $boardKind, $type) {
                foreach ($depths as $d) {
                    if (! $this->has(RackPart::keyFor($boardKind, null, $d - $depthTrim, $w - $widthTrim, $type))) {
                        return false;
                    }
                }

                return $depths !== [];
            }
        ));

        if ($heights === [] || $depths === [] || $widths === []) {
            return null;
        }

        /*
         | The DEFAULT has to be a PAIR that exists, not a height and a depth
         | narrowed separately.
         |
         | Store::rackConfigurator() picks a default height and depth from the
         | sizes the merchant OFFERS. Leave the frame at that exact combination
         | blank and 210 still survives (it pairs with 30/40/50) and 60 still
         | survives (it pairs with 150/180/240/300), yet 210 x 60 itself cannot
         | be built. Picking the two independently reproduces the dead pair and
         | the page opens on a configuration nothing can price.
         |
         | So: keep the merchant's preferred pair when it is buildable, then
         | keep as much of it as possible — their height with another depth,
         | their depth with another height — and only then fall to whatever the
         | price book still has.
         */
        [$defH, $defD] = $this->firstBuildablePair($heights, $depths, $limits['default_height_cm'], $limits['default_depth_cm'], $frame);

        return [
            'heights' => $heights,
            'depths' => $depths,
            'widths' => $widths,
            'default_height_cm' => $defH,
            'default_depth_cm' => $defD,
            'default_width_cm' => in_array($limits['default_width_cm'], $widths, true) ? $limits['default_width_cm'] : $widths[0],
        ];
    }

    /**
     * @param  callable(int,int):bool  $exists
     * @return array{0:int,1:int} the height/depth to open on, or [0, 0] when
     *                            no frame at all can be priced
     */
    private function firstBuildablePair(array $heights, array $depths, int $preferH, int $preferD, callable $exists): array
    {
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
        return ($narrowed['types'] ?? []) !== []
            && $narrowed['heights'] !== []
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
