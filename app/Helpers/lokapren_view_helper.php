<?php

declare(strict_types=1);

if (! function_exists('kategori_nav')) {
    /**
     * Product categories for the header menu, with live product counts.
     *
     * Cached on the service locator for the request, since the header and the
     * catalog page both ask for it.
     *
     * @return list<array<string, mixed>>
     */
    function kategori_nav(): array
    {
        return (new \App\Models\ProductCategoryModel())->withProductCounts();
    }
}

if (! function_exists('sanggar_footer')) {
    /**
     * A short list of verified sanggar for the footer and home page.
     *
     * @return list<array<string, mixed>>
     */
    function sanggar_footer(int $limit = 6): array
    {
        $sellers = new \App\Models\SellerProfileModel();

        return $sellers->newRows(
            $sellers->newQuery()
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->where('is_verified', 1)
                ->orderBy('rating_average', 'DESC'),
            $limit
        );
    }
}

if (! function_exists('ulasan_terbaru')) {
    /**
     * The newest published reviews, for the home page's social-proof band.
     *
     * @return list<array<string, mixed>>
     */
    function ulasan_terbaru(int $limit = 6): array
    {
        $reviews = new \App\Models\ReviewModel();

        return $reviews->newRows(
            $reviews->newQuery()
                ->select('reviews.*, products.name AS product_name, products.slug AS product_slug, user_profiles.full_name AS customer_name, user_profiles.photo_path AS customer_photo')
                ->join('products', 'products.id = reviews.product_id', 'left')
                ->join('user_profiles', 'user_profiles.user_id = reviews.customer_id', 'left')
                ->where('reviews.status', \App\Models\ReviewModel::STATUS_PUBLISHED)
                ->orderBy('reviews.created_at', 'DESC'),
            $limit
        );
    }
}

if (! function_exists('bintang')) {
    /**
     * A read-only star rating.
     *
     * The average is a DECIMAL, so it is rounded for display only — the raw
     * value stays untouched everywhere it is used as a number.
     *
     * @param mixed $average
     * @param mixed $count
     */
    function bintang($average, $count = null, bool $showCount = true): string
    {
        $average = (float) $average;
        $full    = (int) floor($average);
        $half    = ($average - $full) >= 0.5 ? 1 : 0;

        $html = '<span class="lp-stars" role="img" aria-label="'
            . esc(number_format($average, 1, ',', '.'), 'attr')
            . ' dari 5">';

        for ($i = 1; $i <= 5; $i++) {
            $html .= $i <= $full ? '★' : ($i === $full + 1 && $half === 1 ? '⯨' : '☆');
        }

        $html .= '</span>';

        if ($showCount && $count !== null) {
            $html .= '<small class="lp-stars__count">(' . esc((string) (int) $count) . ')</small>';
        }

        return $html;
    }
}

if (! function_exists('status_pesan_pill')) {
    /**
     * An order status as a coloured pill.
     *
     * The colour is a small whitelist derived from the status itself, so a page
     * can never choose an arbitrary CSS class, and the label comes from the
     * service rather than being retyped in every view.
     *
     * @param mixed $status
     */
    function status_pesan_pill($status): string
    {
        $status = (string) $status;

        $tone = match ($status) {
            'cancelled', 'refunded' => 'bad',
            'pending_payment'       => 'warn',
            default                 => 'ok',
        };

        $labels = \App\Services\OrderService::statusLabels();
        $label  = $labels[$status] ?? $status;

        return '<span class="lp-pill lp-pill--' . esc($tone, 'attr') . '">'
            . esc($label) . '</span>';
    }
}

if (! function_exists('lokasi_teks')) {
    /**
     * Join the free-text place a seller or address typed.
     *
     * Geography has no lookup table behind it, so this simply concatenates
     * whatever was saved and skips the empty parts.
     *
     * `$prefix` names the column family to read: '' for seller profiles and
     * addresses (`village`), 'ship_' for a snapshot stored on an order
     * (`ship_village`). Orders copy the destination at checkout, so their
     * place must be read from the snapshot and never joined back to the
     * buyer's current address.
     *
     * @param array<string, mixed> $row
     */
    function lokasi_teks(array $row, string $glue = ', ', string $prefix = ''): string
    {
        $parts = [];

        foreach (['village', 'district', 'regency', 'province'] as $part) {
            $value = $row[$prefix . $part] ?? null;

            if ($value !== null && $value !== '') {
                $parts[] = $value;
            }
        }

        return implode($glue, $parts);
    }
}
