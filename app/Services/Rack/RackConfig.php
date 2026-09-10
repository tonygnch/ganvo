<?php

namespace App\Services\Rack;

/**
 * A rack the customer has described: one height, one depth, one level count,
 * and an ordered list of NOMINAL section widths.
 *
 * Height, depth and levels are shared across the whole run by design (§8).
 * That is what makes the frame-sharing arithmetic unambiguous — a run where
 * section 3 had six levels and section 4 had four would need a different pin
 * at every mismatched frame, and that is a later version.
 *
 * This is the ONLY place untrusted input becomes a configuration. Nothing
 * downstream re-checks, so nothing downstream may be reached another way.
 */
final class RackConfig
{
    /** @param list<int> $segments nominal section widths, in order */
    private function __construct(
        public readonly int $heightCm,
        public readonly int $depthCm,
        public readonly int $levels,
        public readonly array $segments,
    ) {}

    /**
     * @param  array  $input  raw, from the browser
     * @param  array  $limits  Store::rackConfigurator()
     *
     * @throws RackException
     */
    public static function fromArray(array $input, array $limits): self
    {
        $height = (int) ($input['height'] ?? $input['height_cm'] ?? 0);
        $depth = (int) ($input['depth'] ?? $input['depth_cm'] ?? 0);
        $levels = (int) ($input['levels'] ?? 0);

        self::assertAllowed($height, $limits['heights'], 'cfg_err_height');
        self::assertAllowed($depth, $limits['depths'], 'cfg_err_depth');
        self::assertAllowed($levels, $limits['levels'], 'cfg_err_levels');

        $raw = $input['segments'] ?? [];
        if (! is_array($raw) || $raw === []) {
            throw new RackException('cfg_err_no_sections');
        }

        // Guard the loop before walking it: max length ÷ the narrowest bay is
        // the most sections that could ever be legal, and a payload claiming
        // more is not a customer, it is a fetch loop.
        $narrowest = min($limits['widths']);
        $ceiling = (int) ceil($limits['max_length_cm'] / max(1, $narrowest));
        if (count($raw) > $ceiling) {
            throw new RackException('cfg_err_too_long', [
                'metres' => number_format($limits['max_length_cm'] / 100, 2),
            ]);
        }

        $segments = [];
        foreach ($raw as $width) {
            $width = (int) $width;
            self::assertAllowed($width, $limits['widths'], 'cfg_err_width');
            $segments[] = $width;
        }

        $total = array_sum($segments);
        if ($total > $limits['max_length_cm']) {
            throw new RackException('cfg_err_too_long', [
                'metres' => number_format($limits['max_length_cm'] / 100, 2),
            ]);
        }

        return new self($height, $depth, $levels, $segments);
    }

    /** Trusted construction, for a configuration already read back from the DB. */
    public static function of(int $heightCm, int $depthCm, int $levels, array $segments): self
    {
        return new self($heightCm, $depthCm, $levels, array_values(array_map('intval', $segments)));
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

    /* ---------------------------------------------------------------
     | The construction arithmetic (§2-4). Frames are SHARED: a new
     | section brings one new upright and inherits its neighbour's.
     |
     | The pin counts balance only because an extension pin is
     | DOUBLE-SIDED — it carries a shelf on each side of a shared
     | upright. Every shelf needs 4 corners, so S×L shelves need 4SL
     | supports; the two end frames give 4L, and each of the S−1 shared
     | frames gives 2L pins serving 4L corners. 4L + 4L(S−1) = 4LS.
     |
     | Anyone "fixing" extensionPins() to 4·L·(S−1) doubles the order.
     | RackCalculatorTest asserts the balance directly.
     --------------------------------------------------------------- */

    public function frameCount(): int
    {
        return $this->sectionCount() + 1;
    }

    public function shelfCount(): int
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

    /** One brace every other bay, starting at the first (§4). */
    public function crossBraceCount(): int
    {
        return (int) ceil($this->sectionCount() / 2);
    }

    /** Zero-based indexes of the bays that carry a brace — the renderer draws these. */
    public function bracedSectionIndexes(): array
    {
        $out = [];
        for ($i = 0; $i < $this->sectionCount(); $i += 2) {
            $out[] = $i;
        }

        return $out;
    }

    public function toArray(): array
    {
        return [
            'height' => $this->heightCm,
            'depth' => $this->depthCm,
            'levels' => $this->levels,
            'segments' => $this->segments,
        ];
    }
}
