<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Turns the free-text opening_hours a store keeps on its profile into a
 * current open/closed status.
 *
 * The stored shape is two lines, a day range and an hour range:
 *
 *     Senin - Sabtu
 *     08.00 - 17.00
 *
 * Anything it cannot read is reported as unknown rather than guessed, so the
 * page never claims a workshop is open when the text says nothing usable.
 */
final class StoreHours
{
    private const ZONE = 'Asia/Jakarta';

    private const DAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    private const EVERY_DAY = ['setiap', 'hari', 'all', 'daily', 'minggu'];

    /**
     * @return array{days: string, hours: string, zone: string, state: string, open: bool, label: string}
     */
    public static function describe(?string $raw, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone(self::ZONE));

        $status = [
            'days'  => '',
            'hours' => '',
            'zone'  => 'WIB',
            'state' => 'unknown',
            'open'  => false,
            'label' => 'Jam buka belum dicantumkan',
        ];

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/', (string) $raw) ?: []),
            static fn (string $line): bool => $line !== '',
        ));

        if ($lines === []) {
            return $status;
        }

        $status['days']  = $lines[0];
        $status['hours'] = $lines[1] ?? '';

        $window = self::parseWindow($status['hours']);
        $days   = self::parseDays($status['days']);

        if ($window === null || $days === []) {
            return $status;
        }

        $today   = (int) $now->format('N');
        $minutes = ((int) $now->format('G')) * 60 + (int) $now->format('i');
        $suffix  = $status['hours'] . ' ' . $status['zone'];

        if (in_array($today, $days, true)) {
            $status['state'] = 'open';
            $status['open']  = $minutes >= $window[0] && $minutes < $window[1];
            $status['label'] = $status['open']
                ? 'Buka Hari Ini (' . $suffix . ')'
                : 'Tutup Saat Ini (buka ' . $suffix . ')';

            return $status;
        }

        $status['state'] = 'soon';
        $status['label'] = 'Tutup ' . self::DAY_NAMES[$today]
            . ' · buka ' . self::DAY_NAMES[self::nextDay($days, $today)] . ' (' . $suffix . ')';

        return $status;
    }

    /**
     * @return array{0: int, 1: int}|null Start and end in minutes, null when unreadable.
     */
    private static function parseWindow(string $text): ?array
    {
        $matched = preg_match(
            '/(\d{1,2})[.:](\d{2})\s*(?:-|–|—|s\/d|to)\s*(\d{1,2})[.:](\d{2})/i',
            $text,
            $parts,
        );

        if ($matched !== 1) {
            return null;
        }

        $start = ((int) $parts[1]) * 60 + (int) $parts[2];
        $end   = ((int) $parts[3]) * 60 + (int) $parts[4];

        // An overnight range cannot be trusted without a closing day, so it is
        // reported as unreadable instead of being stretched into the morning.
        if ($end <= $start || $end > 24 * 60) {
            return null;
        }

        return [$start, $end];
    }

    /**
     * @return list<int> ISO weekday numbers the text covers.
     */
    private static function parseDays(string $text): array
    {
        $lower = mb_strtolower($text);

        foreach (self::EVERY_DAY as $token) {
            if (str_contains($lower, $token)) {
                return array_keys(self::DAY_NAMES);
            }
        }

        $parts = preg_split('/\s*(?:-|–|—|s\/d|to)\s*/i', trim($text)) ?: [];

        $found = [];

        foreach ($parts as $part) {
            $index = array_search(ucfirst(mb_strtolower(trim($part))), self::DAY_NAMES, true);

            if ($index !== false) {
                $found[] = $index;
            }
        }

        if ($found === []) {
            return [];
        }

        if (count($found) === 1) {
            return $found;
        }

        $start  = (int) $found[0];
        $end    = (int) end($found);
        $days   = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day      = (($start - 1 + $offset) % 7) + 1;
            $days[]   = $day;

            if ($day === $end) {
                break;
            }
        }

        return $days;
    }

    /**
     * @param list<int> $days
     */
    private static function nextDay(array $days, int $today): int
    {
        for ($offset = 1; $offset <= 7; $offset++) {
            $candidate = (($today - 1 + $offset) % 7) + 1;

            if (in_array($candidate, $days, true)) {
                return $candidate;
            }
        }

        return $today;
    }
}