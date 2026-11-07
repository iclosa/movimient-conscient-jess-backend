<?php

namespace App\Support;

class Money
{
    /**
     * Formatea céntimos como importe en euros ("12,50 €").
     */
    public static function format(?int $cents): string
    {
        $cents ??= 0;

        return number_format($cents / 100, 2, ',', '.').' €';
    }

    /**
     * Convierte un importe escrito por la persona ("12,50" o "12.5") a céntimos.
     */
    public static function toCents(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $normalized = str_replace(',', '.', (string) $value);

        return (int) round(((float) $normalized) * 100);
    }
}
