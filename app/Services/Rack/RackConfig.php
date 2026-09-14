<?php

namespace App\Services\Rack;

use App\Models\RackPart;

/**
 * A rack the customer has described: a model, one height, one depth, one level
 * count, and an ordered list of NOMINAL section widths — plus, for the office
 * rack, how deep its desk plate is, and any cross-braces added by hand.
 *
 * Model, height, depth and levels are shared across the whole run by design
 * (§8). That is what makes the frame-sharing arithmetic unambiguous — a run
 * where section 3 had six levels and section 4 had four would need a different
 * pin at every mismatched frame, and a run mixing an office section into plain
 * shelving is the same problem with a desk in it. Both are a later version.
 *
 * This is the ONLY place untrusted input becomes a configuration. Nothing
 * downstream re-checks, so nothing downstream may be reached another way.
 */
final class RackConfig
{
    /** The plain shelving rack: every level a flat shelf. */
    public const TYPE_SINGLE = 'single';

    /** One level of each section is a desk: a deeper shelf, at working height. */
    public const TYPE_OFFICE = 'office';

    /** Every level is a rimmed wine tray instead of a flat shelf. */
    public const TYPE_WINE = 'wine';

    public const TYPES = [self::TYPE_SINGLE, self::TYPE_OFFICE, self::TYPE_WINE];

    /** @param list<int> $segments nominal section widths, in order */
    private function __construct(
        public readonly int $heightCm,
        public readonly int $depthCm,
        public readonly int $levels,
        public readonly array $segments,
        public readonly string $type = self::TYPE_SINGLE,
        /** Nominal depth of the office desk plate; null for the other models. */
        public readonly ?int $deskDepthCm = null,
        /** @var list<int> sections braced by hand — see isAutoBraced() for the rest */
        public readonly array $extraBraces = [],
        /** @var list<int> the office rack's sections that carry the desk; empty for the other models */
        public readonly array $deskSections = [],
    ) {}

    /**
     * @param  array  $input  raw, from the browser
     * @param  array  $limits  Store::rackConfigurator(), narrowed by the price book
     *
     * @throws RackException
     */
    public static function fromArray(array $input, array $limits): self
    {
        $type = (string) ($input['type'] ?? self::TYPE_SINGLE);
        if (! in_array($type, $limits['types'] ?? [self::TYPE_SINGLE], true)) {
            throw new RackException('cfg_err_type');
        }

        $height = (int) ($input['height'] ?? $input['height_cm'] ?? 0);
        $depth = (int) ($input['depth'] ?? $input['depth_cm'] ?? 0);
        $levels = (int) ($input['levels'] ?? 0);

        // Each type is sold in the sizes its own price tables cover
        // (RackPriceBook::narrow); limits that were never narrowed apply to all.
        $sizes = $limits['by_type'][$type] ?? $limits;

        self::assertAllowed($height, $sizes['heights'], 'cfg_err_height');
        self::assertAllowed($depth, $sizes['depths'], 'cfg_err_depth');
        self::assertAllowed($levels, $limits['levels'], 'cfg_err_levels');

        $deskDepth = null;
        if ($type === self::TYPE_OFFICE) {
            // An office rack gives one level to the desk; it needs another for a shelf.
            if ($levels < 2) {
                throw new RackException('cfg_err_levels');
            }

            // The desk is a plate at least as deep as the rack's own shelves,
            // chosen by the customer. Left out, it is the deepest on offer —
            // a "bigger plate" is the whole point of the model.
            $deskDepths = self::deskDepthsFor($depth, $sizes['depths']);
            $deskDepth = (int) ($input['desk_depth'] ?? $input['desk_depth_cm'] ?? 0);
            if ($deskDepth === 0) {
                $deskDepth = max($deskDepths);
            }
            self::assertAllowed($deskDepth, $deskDepths, 'cfg_err_desk_depth');
        }

        $raw = $input['segments'] ?? [];
        if (! is_array($raw) || $raw === []) {
            throw new RackException('cfg_err_no_sections');
        }

        // Guard the loop before walking it: max length ÷ the narrowest bay is
        // the most sections that could ever be legal, and a payload claiming
        // more is not a customer, it is a fetch loop.
        $narrowest = min($sizes['widths']);
        $ceiling = (int) ceil($limits['max_length_cm'] / max(1, $narrowest));
        if (count($raw) > $ceiling) {
            throw new RackException('cfg_err_too_long', [
                'metres' => number_format($limits['max_length_cm'] / 100, 2),
            ]);
        }

        $segments = [];
        foreach ($raw as $width) {
            $width = (int) $width;
            self::assertAllowed($width, $sizes['widths'], 'cfg_err_width');
            $segments[] = $width;
        }

        $total = array_sum($segments);
        if ($total > $limits['max_length_cm']) {
            throw new RackException('cfg_err_too_long', [
                'metres' => number_format($limits['max_length_cm'] / 100, 2),
            ]);
        }

        // Braces added by hand. A section that is not there is an error; a
        // section that carries a brace anyway simply has one — it is not two.
        $extra = [];
        $rawBraces = $input['extra_braces'] ?? [];
        if (! is_array($rawBraces)) {
            throw new RackException('cfg_err_brace');
        }
        foreach ($rawBraces as $index) {
            if (! is_numeric($index) || (int) $index < 0 || (int) $index >= count($segments)) {
                throw new RackException('cfg_err_brace');
            }
            if (! self::isAutoBraced((int) $index)) {
                $extra[(int) $index] = (int) $index;
            }
        }
        ksort($extra);

        // The sections that carry the office desk. Left out, every section
        // does; given, there must be at least one — without a desk it is not
        // an office rack.
        $desks = [];
        if ($type === self::TYPE_OFFICE) {
            $rawDesks = $input['desk_sections'] ?? null;
            if ($rawDesks === null) {
                $desks = array_keys($segments);
            } else {
                if (! is_array($rawDesks)) {
                    throw new RackException('cfg_err_desk_sections');
                }
                foreach ($rawDesks as $index) {
                    if (! is_numeric($index) || (int) $index < 0 || (int) $index >= count($segments)) {
                        throw new RackException('cfg_err_desk_sections');
                    }
                    $desks[(int) $index] = (int) $index;
                }
                if ($desks === []) {
                    throw new RackException('cfg_err_desk_sections');
                }
                ksort($desks);
                $desks = array_values($desks);
            }
        }

        return new self($height, $depth, $levels, $segments, $type, $deskDepth, array_values($extra), $desks);
    }

    /**
     * Trusted construction, for a configuration already read back from the DB.
     * A model this code does not know is read as the plain rack rather than
     * trusted blindly, an office rack stored without a desk depth gets a desk
     * as deep as its shelves, and a stored brace that no longer names a
     * hand-braced section of this run is dropped. An office rack stored
     * without desk sections has the desk on every section, as it did before
     * the customer could choose.
     */
    public static function of(
        int $heightCm,
        int $depthCm,
        int $levels,
        array $segments,
        string $type = self::TYPE_SINGLE,
        ?int $deskDepthCm = null,
        array $extraBraces = [],
        ?array $deskSections = null,
    ): self {
        $type = in_array($type, self::TYPES, true) ? $type : self::TYPE_SINGLE;
        $segments = array_values(array_map('intval', $segments));

        $extra = [];
        foreach ($extraBraces as $index) {
            $index = (int) $index;
            if ($index >= 0 && $index < count($segments) && ! self::isAutoBraced($index)) {
                $extra[$index] = $index;
            }
        }
        ksort($extra);

        $desks = [];
        if ($type === self::TYPE_OFFICE) {
            foreach ($deskSections ?? array_keys($segments) as $index) {
                $index = (int) $index;
                if ($index >= 0 && $index < count($segments)) {
                    $desks[$index] = $index;
                }
            }
            ksort($desks);
            // a stored list that no longer names any section of this run falls back to all of them
            $desks = $desks === [] ? array_keys($segments) : array_values($desks);
        }

        return new self(
            $heightCm,
            $depthCm,
            $levels,
            $segments,
            $type,
            $type === self::TYPE_OFFICE ? ($deskDepthCm ?: $depthCm) : null,
            array_values($extra),
            $desks,
        );
    }

    /** The desk depths an office rack of this depth may have: no shallower than the rack. */
    public static function deskDepthsFor(int $depthCm, array $depths): array
    {
        return array_values(array_filter($depths, fn (int $d) => $d >= $depthCm));
    }

    /**
     * The price-book board only this model uses, or null when it is priced
     * from the ordinary tables. The office desk is a shelf, so the office rack
     * needs nothing the plain rack does not.
     */
    public static function modelBoardKind(string $type): ?string
    {
        return $type === self::TYPE_WINE ? RackPart::KIND_WINE_TRAY : null;
    }

    /** Every other section carries a brace as standard, starting at the first (§4). */
    public static function isAutoBraced(int $index): bool
    {
        return $index % 2 === 0;
    }

    private static function assertAllowed(int $value, array $allowed, string $reasonKey): void
    {
        if (! in_array($value, $allowed, true)) {
            throw new RackException($reasonKey);
        }
    }

    public function sectionCount(): int
    {
        return count($this->segments);
    }

    public function totalLengthCm(): int
    {
        return array_sum($this->segments);
    }

    /** Whether this section carries the office desk. */
    public function isDeskSection(int $index): bool
    {
        return $this->type === self::TYPE_OFFICE && in_array($index, $this->deskSections, true);
    }

    /**
     * The boards one section carries, by kind, for this model:
     *
     *   single   every level a shelf
     *   wine     every level a wine tray, in place of the shelf
     *   office   on a desk section, the desk plate at desk height and shelves
     *            above; on the others, a shelf at that level too
     *
     * Every model puts exactly ONE board on each level, and an office rack
     * keeps the same level heights along the whole run whichever sections
     * have the desk, which is why the pin arithmetic below does not change.
     *
     * @return array<string,int> kind => boards in that section, in BOM order
     */
    public function boardsForSection(int $index): array
    {
        return match (true) {
            $this->type === self::TYPE_WINE => [RackPart::KIND_WINE_TRAY => $this->levels],
            $this->isDeskSection($index) => [RackPart::KIND_SHELF => $this->levels - 1, RackPart::KIND_DESK_TOP => 1],
            default => [RackPart::KIND_SHELF => $this->levels],
        };
    }

    /* ---------------------------------------------------------------
     | The construction arithmetic (§2-4). Frames are SHARED: a new
     | section brings one new upright and inherits its neighbour's.
     |
     | The pin counts balance only because an extension pin is
     | DOUBLE-SIDED — it carries a board on each side of a shared
     | upright. Every board needs 4 corners, so S×L boards need 4SL
     | supports; the two end frames give 4L, and each of the S−1 shared
     | frames gives 2L pins serving 4L corners. 4L + 4L(S−1) = 4LS.
     | A desk plate or a wine tray sits on pins exactly as a shelf does.
     |
     | Anyone "fixing" extensionPins() to 4·L·(S−1) doubles the order.
     | RackCalculatorTest asserts the balance directly.
     --------------------------------------------------------------- */

    public function frameCount(): int
    {
        return $this->sectionCount() + 1;
    }

    /** Flat shelves only — see boardCount() for every board of every kind. */
    public function shelfCount(): int
    {
        $count = 0;
        for ($i = 0; $i < $this->sectionCount(); $i++) {
            $count += $this->boardsForSection($i)[RackPart::KIND_SHELF] ?? 0;
        }

        return $count;
    }

    public function boardCount(): int
    {
        return $this->sectionCount() * $this->levels;
    }

    public function endPinCount(): int
    {
        return 4 * $this->levels;
    }

    public function extensionPinCount(): int
    {
        return 2 * $this->levels * ($this->sectionCount() - 1);
    }

    /** The standard brace on every other bay, plus any the customer added (§4). */
    public function crossBraceCount(): int
    {
        return count($this->bracedSectionIndexes());
    }

    /** Zero-based indexes of the bays that carry a brace — the renderer draws these. */
    public function bracedSectionIndexes(): array
    {
        $out = [];
        for ($i = 0; $i < $this->sectionCount(); $i++) {
            if (self::isAutoBraced($i) || in_array($i, $this->extraBraces, true)) {
                $out[] = $i;
            }
        }

        return $out;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'height' => $this->heightCm,
            'depth' => $this->depthCm,
            'desk_depth' => $this->deskDepthCm,
            'levels' => $this->levels,
            'segments' => $this->segments,
            'extra_braces' => $this->extraBraces,
            'desk_sections' => $this->type === self::TYPE_OFFICE ? $this->deskSections : null,
        ];
    }
}
