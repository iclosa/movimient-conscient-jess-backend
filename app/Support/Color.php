<?php

namespace App\Support;

class Color
{
    /**
     * Color de text (blanc o fosc) amb millor contrast sobre un fons hexadecimal.
     */
    public static function readableText(?string $hex): string
    {
        $dark = '#17141C';
        $light = '#FFFFFF';

        if (! $hex || ! preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
            return $light;
        }

        $luminance = self::luminance($hex);
        $contrastWithLight = 1.05 / ($luminance + 0.05);
        $contrastWithDark = ($luminance + 0.05) / (self::luminance($dark) + 0.05);

        return $contrastWithLight >= $contrastWithDark ? $light : $dark;
    }

    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function (string $part) {
            $c = hexdec($part) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
