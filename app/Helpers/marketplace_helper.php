<?php

declare(strict_types=1);

use CodeIgniter\I18n\Time;

/**
 * Presentation helpers for the Lokapren marketplace.
 *
 * Every stored value is machine-readable (integer rupiah, ISO dates, machine
 * slugs). Formatting happens here so nothing in a view has to know that
 * `grand_total` is a BIGINT of rupiah rather than a decimal amount.
 */

if (! function_exists('rupiah')) {
    /**
     * Format integer rupiah for display: 450000 -> "Rp 450.000".
     *
     * Indonesian grouping uses "." as the thousands separator, which is why
     * this is not `number_format(..., 2)`. Negative values (refunds) keep the
     * minus sign in front of the currency word.
     */
    function rupiah(int|float|string|null $amount, bool $withPrefix = true): string
    {
        $amount = (int) $amount;
        $sign   = $amount < 0 ? '-' : '';
        $digits = number_format(abs($amount), 0, ',', '.');

        return $withPrefix ? "{$sign}Rp {$digits}" : "{$sign}{$digits}";
    }
}

if (! function_exists('ringkas_rupiah')) {
    /**
     * Compact rupiah for dashboard tiles: 38650000 -> "Rp 38,65 jt".
     */
    function ringkas_rupiah(int|float|string|null $amount): string
    {
        $amount = (int) $amount;

        if ($amount >= 1_000_000_000) {
            return 'Rp ' . number_format($amount / 1_000_000_000, 2, ',', '.') . ' M';
        }

        if ($amount >= 1_000_000) {
            return 'Rp ' . number_format($amount / 1_000_000, 2, ',', '.') . ' jt';
        }

        if ($amount >= 1_000) {
            return 'Rp ' . number_format($amount / 1_000, 0, ',', '.') . ' rb';
        }

        return rupiah($amount);
    }
}

if (! function_exists('format_tanggal')) {
    /**
     * Indonesian long date: "16 Februari 2025".
     */
    function format_tanggal(?string $date, bool $withTime = false): string
    {
        if ($date === null || $date === '' || str_starts_with($date, '0000')) {
            return '-';
        }

        $time = Time::parse($date);

        if ($time === null) {
            return '-';
        }

        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $formatted = $time->day . ' ' . $months[(int) $time->month] . ' ' . $time->year;

        return $withTime ? $formatted . ', ' . $time->format('H:i') . ' WIB' : $formatted;
    }
}

if (! function_exists('format_jam')) {
    /**
     * Render a TIME column as a short Indonesian clock time ("08.00").
     */
    function format_jam(?string $time): string
    {
        if ($time === null || $time === '') {
            return '-';
        }

        return substr($time, 0, 5);
    }
}

if (! function_exists('waktu_sMART')) {
    /**
     * Compact relative age for chat and review lists: "3 hari lalu".
     */
    function waktu_smart(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '-';
        }

        $time     = Time::parse($datetime);
        $diffDays = $time === null ? 0 : $time->difference(Time::now())->getDays();

        if ($diffDays <= 0) {
            return $time?->format('H:i') . ' WIB' ?? '-';
        }

        if ($diffDays === 1) {
            return 'Kemarin';
        }

        if ($diffDays < 7) {
            return $diffDays . ' hari lalu';
        }

        return format_tanggal($datetime);
    }
}

if (! function_exists('nama_hari')) {
    /**
     * ISO weekday number (1 = Monday) to Indonesian day name.
     *
     * @return array<int, string>
     */
    function nama_hari(): array
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
    }
}

if (! function_exists('inisial')) {
    /**
     * First letters of a display name, for the avatar circles in the design.
     */
    function inisial(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '?';
        }

        $words  = preg_split('/\s+/', $name) ?: [];
        $result = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $result .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $result === '' ? '?' : $result;
    }
}

if (! function_exists('url_gambar')) {
    /**
     * URL for a stored upload path, served through the upload controller so
     * files never sit inside the web root.
     *
     * @return string|null
     */
    function url_gambar(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return site_url('media/' . ltrim($path, '/'));
    }
}
