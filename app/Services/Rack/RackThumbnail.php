<?php

namespace App\Services\Rack;

/**
 * A saved rack as a small picture — the front elevation the configurator
 * draws, reduced to what reads at 64 px: the uprights, the boards level by
 * level (a heavier desk plate on an office rack's desk sections, a wine
 * tray's front board and lean), and the steel X on the braced bays.
 *
 * It stands in for the product photo a rack does not have, in the cart and
 * the cart drawer. Fixed colours, because an <img> cannot read the page's
 * CSS; a fixed frame (10 × 11), because both places crop their image to a
 * box, and a long run drawn at its own proportions would be cropped to its
 * middle bays. The rack is centred in the frame with room around it instead.
 *
 * No text of any kind goes into the picture, so nothing a customer typed can
 * end up in it.
 */
final class RackThumbnail
{
    private const POST_W = 5;

    private const TOP_INSET = 6;

    private const BOT_INSET = 9;

    /** width : height of the frame the rack is fitted into */
    private const FRAME_W = 10;

    private const FRAME_H = 11;

    private const BG = '#1c1a15';

    private const FLOOR = '#34302a';

    private const POST = '#b3844c';

    private const POST_LIT = '#d2a567';

    private const BOARD = '#d8af75';

    private const BOARD_EDGE = '#a97d45';

    private const TRAY_TOP = '#c79e66';

    private const STEEL = '#8d978f';

    /** A run longer than this many times its height is drawn in part, fading out. */
    private const MAX_RUN_TO_HEIGHT = 2.2;

    /** Below this size (cm) parts are drawn at their own thickness; above it, heavier. */
    private const WEIGHT_FROM = 220;

    /** @param  array  $limits  Store::rackConfigurator() */
    public static function svg(RackConfig $config, array $limits): string
    {
        $H = $config->heightCm;

        // A long run shown whole is a thread at 64 px. Draw its first bays —
        // as many as fit in about twice its height — and fade out the rest.
        $segments = [];
        $run = 0;
        foreach ($config->segments as $width) {
            if ($segments !== [] && $run + $width > $H * self::MAX_RUN_TO_HEIGHT) {
                break;
            }
            $segments[] = $width;
            $run += $width;
        }
        $truncated = count($segments) < $config->sectionCount();

        // Real sizes vanish in a big picture: 5 cm posts and 18 mm boards are
        // drawn heavier the bigger the rack, so it still reads as a rack.
        $weight = max(1, max($run, $H) / self::WEIGHT_FROM);
        $postW = self::POST_W * $weight;
        $thick = max(0.5, ($limits['shelf_thickness_mm'] ?? 18) / 10) * $weight;
        $W = $run + $postW;

        // The frame: the rack plus room around it, widened or heightened to 10 × 11.
        $pad = max($W, $H) * 0.1;
        $cw = $W + 2 * $pad;
        $ch = $H + 2 * $pad;
        if ($cw / $ch > self::FRAME_W / self::FRAME_H) {
            $ch = $cw * self::FRAME_H / self::FRAME_W;
        } else {
            $cw = $ch * self::FRAME_W / self::FRAME_H;
        }
        $ox = ($cw - $W) / 2;   // left edge of the drawing
        $oy = ($ch - $H) / 2;   // top of the uprights

        $out = [];
        $out[] = self::rect(0, 0, $cw, $ch, self::BG);
        $out[] = self::rect(0, $oy + $H, $cw, max(0.6, $ch * 0.006), self::FLOOR);

        // pitch lines: one per upright, half a post in from the run's ends
        $px = [$ox + $postW / 2];
        foreach ($segments as $i => $width) {
            $px[] = $px[$i] + $width;
        }

        // the braces go behind everything
        foreach ($config->bracedSectionIndexes() as $i) {
            if ($i >= count($segments)) {
                break;
            }
            $x0 = $px[$i];
            $x1 = $px[$i + 1];
            $y0 = $oy + self::TOP_INSET;
            $y1 = $oy + $H - self::BOT_INSET;
            $sw = max(0.8, $W * 0.004) * $weight;
            $out[] = self::line($x0, $y0, $x1, $y1, $sw);
            $out[] = self::line($x1, $y0, $x0, $y1, $sw);
        }

        // the boards, level by level, bay by bay
        $inset = ($limits['shelf_width_trim_cm'] ?? 3) / 2;
        $levels = self::levels($config, $limits, $thick);
        foreach ($segments as $i => $width) {
            $bx = $px[$i] + $inset;
            $bw = ($px[$i + 1] - $px[$i]) - 2 * $inset;
            foreach ($levels as [$y, $kind]) {
                $y += $oy;
                if ($kind === 'desk' && $config->isDeskSection($i)) {
                    $out[] = self::board($bx, $y, $bw, $thick * 1.5);
                } elseif ($kind === 'tray') {
                    $drop = self::trayDrop($config, $limits);
                    $rim = (float) ($limits['tray_rim_cm'] ?? 5) * $weight;
                    if ($drop > 0.2) {
                        $out[] = self::rect($bx, $y, $bw, $drop, self::TRAY_TOP);
                    }
                    $out[] = self::rect($bx, $y + $drop - $rim, $bw, $rim, self::BOARD_EDGE);
                    $out[] = self::board($bx, $y + $drop, $bw, $thick);
                } else {
                    // a desk level without the desk has a shelf, as low as the desk's underside
                    $out[] = self::board($bx, $y + ($kind === 'desk' ? $thick * 0.5 : 0), $bw, $thick);
                }
            }
        }

        // the uprights, over the board ends
        foreach ($px as $x) {
            $out[] = self::rect($x - $postW / 2, $oy, $postW, $H, self::POST);
            $out[] = self::rect($x - $postW / 2, $oy, $postW * 0.25, $H, self::POST_LIT);
        }

        // the run goes on past the picture
        $defs = '';
        if ($truncated) {
            $defs = '<defs><linearGradient id="more" x1="0" x2="1" y1="0" y2="0">'
                .'<stop offset="0" stop-color="'.self::BG.'" stop-opacity="0"/>'
                .'<stop offset="1" stop-color="'.self::BG.'"/></linearGradient></defs>';
            $fadeFrom = $ox + $W * 0.7;
            $out[] = sprintf('<rect x="%s" y="0" width="%s" height="%s" fill="url(#more)"/>', self::n($fadeFrom), self::n($cw - $fadeFrom), self::n($ch));
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %s %s" preserveAspectRatio="xMidYMid meet">%s%s</svg>',
            self::n($cw),
            self::n($ch),
            $defs,
            implode('', $out)
        );
    }

    /**
     * Where each level's board sits, down from the top of the uprights — the
     * configurator's own spacing (boardLevels() in its view), without the
     * snapping to drilled holes, which is finer than a thumbnail shows.
     *
     * @return list<array{0: float, 1: string}> [y, shelf|desk|tray]
     */
    private static function levels(RackConfig $config, array $limits, float $thick): array
    {
        $L = $config->levels;
        $H = $config->heightCm;
        $out = [];

        if ($config->type === RackConfig::TYPE_OFFICE) {
            $deskY = max(self::TOP_INSET + $thick * 3, $H - (int) ($limits['desk_height_cm'] ?? 75));
            $above = $L - 1;
            $gap = $above > 0 ? ($deskY - self::TOP_INSET) / $above : 0;
            for ($s = 0; $s < $above; $s++) {
                $out[] = [self::TOP_INSET + $gap * $s, 'shelf'];
            }
            $out[] = [$deskY, 'desk'];

            return $out;
        }

        $kind = $config->type === RackConfig::TYPE_WINE ? 'tray' : 'shelf';
        $lowest = $H - self::BOT_INSET - $thick - ($kind === 'tray' ? self::trayDrop($config, $limits) : 0);
        if ($L < 2) {
            return [[($H - $thick) / 2, $kind]];
        }
        $step = ($lowest - self::TOP_INSET) / ($L - 1);
        for ($n = 0; $n < $L; $n++) {
            $out[] = [self::TOP_INSET + $step * $n, $kind];
        }

        return $out;
    }

    /** How far a wine tray's front edge sits below its back edge, seen from the front. */
    private static function trayDrop(RackConfig $config, array $limits): float
    {
        $depth = max(1, $config->depthCm - ($limits['shelf_depth_trim_cm'] ?? 1));

        return $depth * sin(deg2rad((float) ($limits['tray_tilt_deg'] ?? 15)));
    }

    private static function board(float $x, float $y, float $w, float $t): string
    {
        return self::rect($x, $y, $w, $t, self::BOARD)
            .self::rect($x, $y + $t * 0.7, $w, $t * 0.3, self::BOARD_EDGE);
    }

    private static function rect(float $x, float $y, float $w, float $h, string $fill): string
    {
        return sprintf('<rect x="%s" y="%s" width="%s" height="%s" fill="%s"/>', self::n($x), self::n($y), self::n($w), self::n($h), $fill);
    }

    private static function line(float $x1, float $y1, float $x2, float $y2, float $width): string
    {
        return sprintf(
            '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="%s" stroke-linecap="round" opacity=".75"/>',
            self::n($x1), self::n($y1), self::n($x2), self::n($y2), self::STEEL, self::n($width)
        );
    }

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}
