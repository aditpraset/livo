<?php

namespace App\Support;

/**
 * Grafik sederhana (bar & pie) yang dirender jadi gambar PNG (base64 data URI)
 * lewat GD — dipakai di laporan PDF (DomPDF) karena tidak bisa menjalankan
 * JavaScript/chart library di browser biasa.
 */
class PdfChart
{
    private static function font(): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function toDataUri($im): string
    {
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);
        return 'data:image/png;base64,' . base64_encode($data);
    }

    /** Bar chart vertikal sederhana (satu seri). */
    public static function bar(array $labels, array $values, string $barColor = '#2C3E73', int $width = 460, int $height = 220): string
    {
        $im = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        $gray  = imagecolorallocate($im, 218, 218, 218);
        $dark  = imagecolorallocate($im, 70, 70, 70);
        [$r, $g, $b] = self::hexToRgb($barColor);
        $bar = imagecolorallocate($im, $r, $g, $b);
        imagefill($im, 0, 0, $white);

        $padLeft = 26; $padBottom = 24; $padTop = 14; $padRight = 8;
        $chartW = $width - $padLeft - $padRight;
        $chartH = $height - $padTop - $padBottom;
        $max = max(1, ...($values ?: [1]));

        for ($i = 0; $i <= 4; $i++) {
            $y = $padTop + $chartH - ($i / 4) * $chartH;
            imageline($im, $padLeft, (int) $y, $width - $padRight, (int) $y, $gray);
            imagettftext($im, 6.5, 0, 2, (int) $y + 3, $dark, self::font(), (string) round($max * $i / 4));
        }

        $n = max(1, count($values));
        $slot = $chartW / $n;
        $barWidth = min($slot * 0.55, 46);
        foreach (array_values($values) as $i => $v) {
            $barH = $max > 0 ? ($v / $max) * $chartH : 0;
            $x1 = $padLeft + $i * $slot + ($slot - $barWidth) / 2;
            $x2 = $x1 + $barWidth;
            $y2 = $padTop + $chartH;
            $y1 = $y2 - $barH;
            imagefilledrectangle($im, (int) $x1, (int) $y1, (int) $x2, (int) $y2, $bar);
            imagettftext($im, 6.5, 0, (int) $x1, (int) $y1 - 3, $dark, self::font(), (string) $v);
            $label = (string) ($labels[$i] ?? '');
            imagettftext($im, 6.5, 0, (int) ($padLeft + $i * $slot + 2), $height - 8, $dark, self::font(), $label);
        }

        imageline($im, $padLeft, $padTop, $padLeft, $padTop + $chartH, $dark);
        imageline($im, $padLeft, $padTop + $chartH, $width - $padRight, $padTop + $chartH, $dark);

        return self::toDataUri($im);
    }

    /** Pie chart + legend di sisi kanan. */
    public static function pie(array $labels, array $values, int $width = 460, int $height = 220): string
    {
        $im = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        $dark  = imagecolorallocate($im, 70, 70, 70);
        imagefill($im, 0, 0, $white);

        $palette = ['#2C3E73', '#4C6FFF', '#22A699', '#F2A104', '#E63946', '#8E44AD', '#16A085', '#D35400'];
        $total = array_sum($values) ?: 1;
        $cx = 95; $cy = (int) ($height / 2); $r = 80;
        $start = 0;
        $colors = [];

        foreach (array_values($values) as $i => $v) {
            [$rr, $gg, $bb] = self::hexToRgb($palette[$i % count($palette)]);
            $col = imagecolorallocate($im, $rr, $gg, $bb);
            $colors[] = $col;
            $sweep = $total > 0 ? ($v / $total) * 360 : 0;
            $end = $sweep < 0.5 ? $start : $start + $sweep;
            imagefilledarc($im, $cx, $cy, $r * 2, $r * 2, (int) round($start), (int) round($end), $col, IMG_ARC_PIE);
            $start += $sweep;
        }

        $lx = 210; $ly = 24;
        foreach (array_values($labels) as $i => $label) {
            imagefilledrectangle($im, $lx, $ly, $lx + 10, $ly + 10, $colors[$i] ?? $dark);
            $pct = $total > 0 ? round(($values[$i] ?? 0) / $total * 100) : 0;
            imagettftext($im, 7, 0, $lx + 16, $ly + 9, $dark, self::font(), $label . ' (' . $pct . '%)');
            $ly += 20;
            if ($ly > $height - 16) { break; } // batasi legend agar tidak meluber
        }

        return self::toDataUri($im);
    }
}
