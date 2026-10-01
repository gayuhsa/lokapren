<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Formats Indonesian Rupiah from integer minor units.
 *
 * Money is stored as BIGINT rupiah, never as a float and never as a formatted
 * string. Formatting happens here, at the presentation layer only.
 *
 * 15000 -> "Rp15.000"
 */
final class Rupiah
{
    /**
     * @param int|string $amount Rupiah, integer only.
     */
    public static function format(int|string $amount): string
    {
        return 'Rp' . number_format((int) $amount, 0, ',', '.');
    }

    /**
     * Formats with the Indonesian thousands separator only, no currency prefix.
     *
     * Useful for input fields where the prefix would be duplicated by markup.
     *
     * @param int|string $amount
     */
    public static function number(int|string $amount): string
    {
        return number_format((int) $amount, 0, ',', '.');
    }

    /**
     * Parses user input such as "15.000" or "Rp15.000" or "15000" into rupiah.
     *
     * Accepts both separators so copy-pasted Indonesian and plain values work.
     * Returns null when the input is not a usable integer amount, so callers
     * can surface a validation error instead of silently pricing at 0.
     */
    public static function parse(mixed $input): ?int
    {
        if (is_int($input)) {
            return $input >= 0 ? $input : null;
        }

        if (! is_string($input)) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $input) ?? '';

        if ($clean === '') {
            return null;
        }

        $value = (int) $clean;

        return $value > 0 ? $value : null;
    }
}
