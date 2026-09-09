<?php

namespace App\Support;

/**
 * Inline SVG artwork for the PDF reports, returned as base64 data URIs.
 *
 * dompdf renders SVG only through <img src="data:image/svg+xml;...">, and its
 * SVG engine has two limits this class works around:
 *   1. gradients are not supported (they render solid black) — use flat fills;
 *   2. <text> always falls back to a serif face and ignores font-weight — so no
 *      artwork here carries text. Chart labels are HTML laid over the image.
 * Arcs are also split into <=90° segments because a single large arc segment is
 * dropped by the renderer.
 */
class ReportSvg
{
    /** Icon paths on a 24×24 grid, stroked (no fills) unless noted. */
    private const ICONS = [
        'user' => '<circle cx="12" cy="8" r="3.6"/><path d="M4.8 20.5c0-3.8 3.2-6.2 7.2-6.2s7.2 2.4 7.2 6.2"/>',
        'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20.2c0-3.4 2.9-5.6 6.5-5.6s6.5 2.2 6.5 5.6"/><path d="M16.4 5.2a3.2 3.2 0 0 1 0 5.9"/><path d="M17.6 14.9c2.4.6 3.9 2.4 3.9 5.3"/>',
        'mail' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M3.5 6.5 12 13l8.5-6.5"/>',
        'phone' => '<rect x="6.5" y="2.5" width="11" height="19" rx="2.5"/><path d="M10.5 5.6h3"/><circle cx="12" cy="18" r="1.1"/>',
        'doc' => '<path d="M6 2.5h7l5 5v14H6z"/><path d="M13 2.5v5h5"/><path d="M8.8 12.5h6.4M8.8 15.5h6.4M8.8 18.5h4"/>',
        'stack' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
        'chart' => '<path d="M3 20.5h18"/><rect x="4.5" y="11" width="4" height="7"/><rect x="10" y="6.5" width="4" height="11.5"/><rect x="15.5" y="9" width="4" height="9"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 6.5V12l4 2.4"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17"/><path d="M8 2.8v4.2M16 2.8v4.2"/>',
        'trend' => '<path d="M3 20.5h18"/><path d="M5 16.5 10 11l3.5 3.5L20 7"/><path d="M15.5 7H20v4.5"/>',
        'pie' => '<circle cx="12" cy="12" r="9"/><path d="M12 3v9h9"/>',
        'arrow-down' => '<path d="M12 4v14"/><path d="M6 12.5 12 19l6-6.5"/>',
        'target' => '<circle cx="12" cy="12" r="8.6"/><circle cx="12" cy="12" r="4.8"/><circle cx="12" cy="12" r="1.3" fill="CURRENT" stroke="none"/>',
        'gear' => '<circle cx="12" cy="12" r="3.4"/><path d="M12 1.8v3.4M12 18.8v3.4M22.2 12h-3.4M5.2 12H1.8M19.2 4.8l-2.4 2.4M7.2 16.8l-2.4 2.4M19.2 19.2l-2.4-2.4M7.2 7.2 4.8 4.8"/>',
        'shield' => '<path d="M12 2.4 4 5.6v6.1c0 4.7 3.3 8.6 8 10 4.7-1.4 8-5.3 8-10V5.6z"/>',
        'shield-check' => '<path d="M12 2.4 4 5.6v6.1c0 4.7 3.3 8.6 8 10 4.7-1.4 8-5.3 8-10V5.6z"/><path d="m8.4 11.9 2.6 2.7 4.7-5"/>',
        'badge-check' => '<circle cx="12" cy="12" r="8.6"/><path d="m8.2 12.2 2.6 2.6 5-5.4"/>',
        'check' => '<path d="m4.5 12.8 5 5L19.5 6.8"/>',
        'calculator' => '<rect x="4.5" y="2.5" width="15" height="19" rx="2"/><rect x="7.5" y="5.5" width="9" height="3.5"/><path d="M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01M16 17h.01"/>',
        'heart' => '<path d="M12 20.4S3.6 15.5 3.6 9.5A4.6 4.6 0 0 1 12 6.9a4.6 4.6 0 0 1 8.4 2.6c0 6-8.4 10.9-8.4 10.9z"/>',
        'shirt' => '<path d="M8.5 3 4 5.6l2 4 2-1v10.9h8V8.6l2 1 2-4L15.5 3a3.6 3.6 0 0 1-7 0z"/>',
        'graduation' => '<path d="M12 3.4 22 8l-10 4.6L2 8z"/><path d="M6.4 10.4v5.1c0 1.7 2.6 3 5.6 3s5.6-1.3 5.6-3v-5.1"/>',
        'search' => '<circle cx="10.7" cy="10.7" r="6.7"/><path d="m15.6 15.6 4.6 4.6"/>',
        'antenna' => '<circle cx="12" cy="12" r="2.2"/><path d="M7.8 7.8a6 6 0 0 0 0 8.4M16.2 16.2a6 6 0 0 0 0-8.4"/><path d="M4.9 4.9a10 10 0 0 0 0 14.2M19.1 19.1a10 10 0 0 0 0-14.2"/>',
        'handshake' => '<path d="M2.5 11.5 6 8h4l2 1.8L14 8h4l3.5 3.5"/><path d="m6.5 12.5 3.4 3.4a1.7 1.7 0 0 0 2.4 0l.6-.6.9.9a1.6 1.6 0 0 0 2.3-2.3l-.9-.9"/><path d="M2.5 11.5v3.9h2.6M21.5 11.5v3.9h-2.6"/>',
        'bag' => '<rect x="3.5" y="7.5" width="17" height="13" rx="2"/><path d="M8.5 7.5V5.6A2.1 2.1 0 0 1 10.6 3.5h2.8a2.1 2.1 0 0 1 2.1 2.1v1.9"/>',
    ];

    /**
     * Icon as a data URI. Unknown names fall back to a filled dot so a template
     * typo degrades to a bullet rather than a broken image.
     */
    public static function icon(string $name, string $color = '#1e3558', float $stroke = 1.9): string
    {
        $body = self::ICONS[$name] ?? '<circle cx="12" cy="12" r="5" fill="CURRENT" stroke="none"/>';
        $body = str_replace('CURRENT', $color, $body);

        return self::uri(
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" '
            . 'fill="none" stroke="' . $color . '" stroke-width="' . $stroke . '" '
            . 'stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>'
        );
    }

    /**
     * Transparent rule of a fixed width, used as the first row of a panel table:
     * dompdf sizes tables from their content and ignores a declared width that is
     * wider, so a spacer image is the only way to pin a panel to an exact width.
     */
    public static function spacer(int $width, int $height = 1): string
    {
        return self::uri('<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '"><rect width="' . $width . '" height="' . $height . '" fill="none"/></svg>');
    }

    /**
     * Horizontal gridlines for a chart plot area (labels are HTML on top).
     */
    public static function gridlines(int $width, int $height, int $lines = 8, string $color = '#e6ebf3'): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        for ($i = 0; $i <= $lines; $i++) {
            $y = round($height - ($height / $lines) * $i, 2);
            $isBase = $i === 0;
            $svg .= '<line x1="0" y1="' . $y . '" x2="' . $width . '" y2="' . $y . '" stroke="'
                . ($isBase ? '#c9d3e4' : $color) . '" stroke-width="' . ($isBase ? 1.4 : 1) . '"/>';
        }

        return self::uri($svg . '</svg>');
    }

    /**
     * Two-slice donut. $pct is the first colour's share (0-100), drawn clockwise
     * from 12 o'clock.
     */
    public static function donut(float $pct, string $first = '#1e3558', string $second = '#f26722', int $size = 200, float $thickness = 34): string
    {
        $pct = max(0.0, min(100.0, $pct));
        $c = $size / 2;
        $r = ($size - $thickness) / 2 - 1;
        $split = 360 * $pct / 100;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">';
        if ($split > 0.5) {
            $svg .= '<path d="' . self::arc($c, $c, $r, 0, $split) . '" fill="none" stroke="' . $first . '" stroke-width="' . $thickness . '"/>';
        }
        if ($split < 359.5) {
            $svg .= '<path d="' . self::arc($c, $c, $r, $split, 360) . '" fill="none" stroke="' . $second . '" stroke-width="' . $thickness . '"/>';
        }

        return self::uri($svg . '</svg>');
    }

    /**
     * Payback progress rail: a full-width track with the elapsed share filled and
     * a marker knob at the split point.
     */
    public static function progressRail(float $pct, int $width = 420, int $height = 22, string $fill = '#1e3558', string $track = '#dbe3f0'): string
    {
        $pct = max(0.0, min(100.0, $pct));
        $r = $height / 2;
        $x = round($width * $pct / 100, 2);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">'
            . '<rect x="0" y="' . ($r - 4) . '" width="' . $width . '" height="8" rx="4" fill="' . $track . '"/>'
            . '<rect x="0" y="' . ($r - 4) . '" width="' . max(8, $x) . '" height="8" rx="4" fill="' . $fill . '"/>'
            . '<circle cx="' . max($r, min($width - $r, $x)) . '" cy="' . $r . '" r="' . ($r - 2) . '" fill="#ffffff" stroke="' . $fill . '" stroke-width="3"/>'
            . '</svg>';

        return self::uri($svg);
    }

    /**
     * Arc path from $a0 to $a1 degrees (0 = 12 o'clock, clockwise), split into
     * <=90° segments: dompdf's SVG engine drops single large-arc segments.
     */
    private static function arc(float $cx, float $cy, float $r, float $a0, float $a1): string
    {
        $segments = max(1, (int) ceil(abs($a1 - $a0) / 90));
        $step = ($a1 - $a0) / $segments;
        $path = '';
        for ($i = 0; $i <= $segments; $i++) {
            $angle = deg2rad($a0 + $step * $i - 90);
            $x = $cx + $r * cos($angle);
            $y = $cy + $r * sin($angle);
            $path .= $i === 0
                ? sprintf('M%.2f %.2f', $x, $y)
                : sprintf(' A%.2f %.2f 0 0 %d %.2f %.2f', $r, $r, $step > 0 ? 1 : 0, $x, $y);
        }

        return $path;
    }

    private static function uri(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
